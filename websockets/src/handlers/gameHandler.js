import axios from "axios";
import {
    createGame,
    getGames,
    joinGame,
    playCard,
    resolveTrick,
    removeGame,
    handleGameTimeout,
    startNextRound,
} from "../game/engine.js";
import { getBotMove } from "../game/bot.js";
import { getUser } from "./connectionHandler.js";
import { setGameTimeout } from "../utils/timer.js";

const API_URL = process.env.VITE_API_URL || "http://localhost:8000/api";

/**
 * Utilitário para centralizar a criação de headers de autenticação.
 * Suporta tanto a extração direta do socket como de um token string.
 */
const getAuthHeaders = (socketOrToken) => {
    const token = typeof socketOrToken === 'string'
        ? socketOrToken
        : socketOrToken.handshake.auth?.token;

    return {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
        },
    };
};

/**
 * G4: Comunica o desfecho do jogo à API externa para registo de estatísticas e prémios.
 * Apenas processa jogos multiplayer que foram previamente inicializados na API.
 */
const storeGameResults = async (socketOrToken, game) => {
    try {
        if (game.mode === "single") return;
        if (!game.api_id) return;
        if (game.api_completed) return;

        const payload = {
            winner_id: game.winner === "Draw" ? null : game.winner,
            player1_points: game.player1_points || 0,
            player2_points: game.player2_points || 0,
            player1_marks: game.player1_marks || 0,
            player2_marks: game.player2_marks || 0,
            end_reason: game.endReason || "Normal",
        };

        const response = await axios.post(
            `${API_URL}/games/${game.api_id}/complete`,
            payload,
            getAuthHeaders(socketOrToken)
        );

        if (response.status === 200 || response.status === 201) {
            game.api_completed = true;
        }
    } catch (error) {
        console.error(`[API G4 ERROR]`, error.response?.data || error.message);
    }
};

/**
 * G3: Gere a lógica de vitória por falta de comparência ou desistência voluntária.
 * Atribui pontuação máxima ao vencedor e comunica o fim imediato do jogo.
 */
const performResignation = async (io, game, loserId) => {
    const winnerId = String(loserId) === String(game.player1.id)
        ? game.player2.id
        : game.player1.id;

    game.winner = winnerId;
    game.status = "Ended";
    game.endReason = "Resignation/Timeout";

    // Define pontuação máxima para o vencedor por desistência (120 pontos da Bisca)
    if (String(winnerId) === String(game.player1.id)) {
        game.player1_points = 120;
        game.player2_points = 0;
    } else {
        game.player2_points = 120;
        game.player1_points = 0;
    }

    // Se for um Match, o vencedor ganha automaticamente as 4 marcas necessárias
    if (game.isMatch) {
        if (String(winnerId) === String(game.player1.id)) {
            game.player1_marks = 4;
        } else {
            game.player2_marks = 4;
        }
    }

    io.to(`game-${game.id}`).emit("game-over", game);
    
    // Utiliza o token de sistema guardado na criação para garantir a persistência na API
    const tokenToUse = game.systemToken; 
    await storeGameResults(tokenToUse, game);

    // Remove o jogo da memória após um breve delay para garantir a entrega dos sockets
    setTimeout(() => {
        removeGame(game.id);
        const lobbyGames = getGames().filter(g => g.status === "Pending" && g.mode !== "single");
        io.emit("games", lobbyGames);
    }, 1000);
};

/**
 * HELPER DE INJEÇÃO: Liga o motor de jogo (engine) a este handler de eventos.
 * Permite que o motor dispare a desistência sem conhecer a lógica de rede/IO.
 */
const attachTimeoutLogic = (game, io) => {
    game.onTimeout = (gId, turnPlayerId) => {
        console.log(`⚡ [HANDLER] Timeout recebido no jogo ${gId}. A penalizar ${turnPlayerId}`);
        performResignation(io, game, turnPlayerId);
    };
};

/**
 * Lógica de inteligência artificial para o Singleplayer.
 * Simula um tempo de pensamento antes de executar a jogada do bot.
 */
const processBotTurn = (io, socket, gameId) => {
    setTimeout(() => {
        const currentGame = getGames().find((g) => g.id === gameId);
        if (!currentGame || currentGame.status !== "Playing" || String(currentGame.turnPlayerId) !== "bot") return;

        // Cria uma vista simplificada para o bot decidir a melhor carta
        const botViewOfGame = {
            ...currentGame,
            player2Hand: currentGame.player2Hand,
            trumpSuit: currentGame.trumpSuit,
            table: currentGame.table,
            deck: currentGame.deck,
        };

        const cardToPlay = getBotMove(botViewOfGame);
        
        if (cardToPlay) {
            const result = playCard(currentGame.id, currentGame.player2.id, cardToPlay.id);
            if (result.valid) {
                io.to(`game-${currentGame.id}`).emit("game-update", result.game);
                checkTrickResolution(io, socket, result.game);
            }
        }
    }, 1500); // Delay de 1.5s para parecer natural
};

/**
 * Coordena a resolução das vazas (tricks) e a transição entre rondas ou fim do jogo.
 */
const checkTrickResolution = (io, socket, game) => {
    if (game.table.length === 2) {
        // Delay visual para os jogadores verem as duas cartas na mesa antes de recolher
        setTimeout(async () => {
            const updatedGame = resolveTrick(game.id); 
            if (!updatedGame) return;

            io.to(`game-${updatedGame.id}`).emit("game-update", updatedGame);

            if (updatedGame.status === "Ended") {
                // Finaliza o jogo e guarda os resultados
                io.to(`game-${updatedGame.id}`).emit("game-over", updatedGame);
                await storeGameResults(socket, updatedGame);
                setTimeout(() => {
                    removeGame(updatedGame.id);
                    io.emit("games", getGames().filter(g => g.status === "Pending" && g.mode !== "single"));
                }, 5000);

            } else if (updatedGame.status === "RoundEnded") {
                // Inicia nova ronda após delay para visualização de pontuação parcial
                setTimeout(() => {
                    const nextRoundGame = startNextRound(updatedGame.id);
                    io.to(`game-${nextRoundGame.id}`).emit("game-update", nextRoundGame);
                    
                    if (String(nextRoundGame.turnPlayerId) === "bot") {
                        processBotTurn(io, socket, nextRoundGame.id);
                    }
                }, 3000);

            } else {
                // O jogo continua na mesma ronda; verifica se o próximo a jogar é o bot
                if (String(updatedGame.turnPlayerId) === "bot") {
                    processBotTurn(io, socket, updatedGame.id);
                }
            }
        }, 2000);
    }
};

/**
 * Notifica os utilizadores no Lobby sobre alterações nos jogos disponíveis.
 */
const emitLobbyUpdate = (io) => {
    const lobbyGames = getGames().filter(g => g.status === "Pending" && g.mode !== "single");
    io.emit("games", lobbyGames);
};

/**
 * Handler principal para todos os eventos de socket relacionados com a partida.
 */
export const handleGameEvents = (io, socket) => {
    socket.on("get-games", () => emitLobbyUpdate(io));

    /**
     * Criação de uma nova instância de jogo (Single ou Multi).
     */
    socket.on("create-game", (options) => {
        let user = getUser(socket.id);
        if (!user && options.mode !== "single") return socket.emit("game-error", "Não autenticado.");

        const game = createGame(
            options.type,
            user || { id: `anon-${socket.id}`, nickname: "Convidado" },
            options.mode || "multi",
            options.isMatch ? options.stake : 2,
            !!options.isMatch
        );

        // Guarda o token de quem criou para operações administrativas/API posteriores
        if (socket.handshake.auth?.token) {
            game.systemToken = socket.handshake.auth.token;
        }

        socket.join(`game-${game.id}`);

        if (game.mode === "single") {
            // No modo Singleplayer, adiciona o bot e inicia sem validação de API/Token
            joinGame(game.id, { id: "bot", nickname: "BiscaBot", isBot: true });
            io.to(`game-${game.id}`).emit("game-start", game);
            
            if (String(game.turnPlayerId) === "bot") {
                processBotTurn(io, socket, game.id);
            }
        } else {
            // No Multiplayer, injeta a lógica de timeout e atualiza o lobby
            attachTimeoutLogic(game, io); 
            emitLobbyUpdate(io);
            socket.emit("game-created", game);
        }
    });

    /**
     * Entrada de um segundo jogador numa sala pendente.
     */
    socket.on("join-game", async (gameID) => {
        const user = getUser(socket.id);
        const game = getGames().find((g) => g.id === gameID);

        // Validações de segurança: existência do jogo, permissões de Admin ou auto-jogo
        if (!game || user?.type === "A") {
            return socket.emit("game-error", "Ação inválida ou utilizador não autorizado.");
        }

        if (game.player1.id === user.id) {
            return socket.emit("game-error", "Não podes jogar contra ti próprio.");
        }

        const updatedGame = joinGame(gameID, user);

        if (updatedGame) {
            socket.join(`game-${updatedGame.id}`);
            attachTimeoutLogic(updatedGame, io);
            emitLobbyUpdate(io);

            if (updatedGame.isMatch) {
                // Partidas Match exigem negociação de stake no Setup
                updatedGame.status = "Setup";
                updatedGame.stakeTurnPlayerId = user.id;
                io.to(`game-${updatedGame.id}`).emit("game-start", updatedGame);
            } 
            else {
                // Jogos Standalone tentam cobrar a entrada imediatamente via API
                try {
                    if (!updatedGame.readyPlayers) updatedGame.readyPlayers = new Set();
                    updatedGame.readyPlayers.add(String(updatedGame.player1.id));
                    updatedGame.readyPlayers.add(String(updatedGame.player2.id));

                    const payload = {
                        player1_id: updatedGame.player1.id,
                        player2_id: updatedGame.player2.id,
                        type: String(updatedGame.type),
                        stake: 2,
                        is_match: 0,
                        mode: 'multi'
                    };

                    const token = updatedGame.systemToken || socket.handshake.auth?.token;

                    const response = await axios.post(
                        `${API_URL}/games/init`, 
                        payload, 
                        getAuthHeaders(token)
                    );

                    updatedGame.api_id = response.data.id;
                    updatedGame.status = "Playing";
                    io.to(`game-${updatedGame.id}`).emit("game-start", updatedGame);
                    
                    setGameTimeout(updatedGame, handleGameTimeout);
                    console.log(`✅ Standalone Multiplayer iniciado e cobrado (ID: ${updatedGame.api_id})`);

                } catch (error) {
                    // Reverte a entrada se o jogador não tiver saldo ou a API falhar
                    console.error("❌ Erro ao iniciar Standalone na API:", error.response?.data || error.message);
                    updatedGame.player2 = null;
                    updatedGame.status = "Pending";
                    socket.leave(`game-${updatedGame.id}`);
                    io.to(`game-${updatedGame.id}`).emit("game-error", "Saldo insuficiente para iniciar o jogo.");
                    emitLobbyUpdate(io);
                }
            }
        }
    });

    /**
     * Negociação de Stake: Proposta de valor.
     */
    socket.on("propose-stake", ({ gameId, amount }) => {
        const game = getGames().find((g) => g.id === gameId);
        const user = getUser(socket.id);
        if (game && game.status === "Setup" && String(game.stakeTurnPlayerId) === String(user.id)) {
            game.pendingStake = amount;
            game.stakeTurnPlayerId = String(user.id) === String(game.player1.id) ? game.player2.id : game.player1.id;
            io.to(`game-${gameId}`).emit("game-update", game);
            socket.broadcast.to(`game-${gameId}`).emit("stake-proposal", { amount, proposer: user.nickname });
        }
    });

    /**
     * Negociação de Stake: Resposta à proposta.
     */
    socket.on("answer-stake-proposal", ({ gameId, accepted }) => {
        const game = getGames().find((g) => g.id === gameId);
        const user = getUser(socket.id);
        if (game && game.status === "Setup") {
            if (accepted && game.pendingStake) game.stake = game.pendingStake;
            game.pendingStake = null;
            game.stakeTurnPlayerId = user.id;
            io.to(`game-${gameId}`).emit("game-update", game);
        }
    });

    /**
     * Sinalização de prontidão para iniciar o Match após o Setup.
     * Inclui a chamada de inicialização na API para validar o saldo dos jogadores.
     */
    socket.on("player-ready", async ({ gameId }) => {
        const game = getGames().find((g) => g.id === gameId);
        if (!game) return;

        if (!game.readyPlayers) game.readyPlayers = new Set();
        game.readyPlayers.add(String(getUser(socket.id).id));

        if (game.readyPlayers.size >= 2 && game.mode === "multi") {
            if (!game.api_id) {
                try {
                    const payload = {
                        player1_id: game.player1.id,
                        player2_id: game.player2.id,
                        type: String(game.type),
                        stake: game.stake,
                        is_match: game.isMatch ? 1 : 0,
                        mode: 'multi'
                    };

                    const token = game.systemToken || socket.handshake.auth?.token;

                    const response = await axios.post(
                        `${API_URL}/games/init`, 
                        payload, 
                        { headers: { Authorization: `Bearer ${token}` } }
                    );

                    game.api_id = response.data.id;
                    if (response.data.match_id) {
                        game.match_id = response.data.match_id;
                    }

                    console.log(`✅ Jogo ${game.isMatch ? 'Match' : 'Standalone'} registado. ID: ${game.api_id}`);

                } catch (error) {
                    console.error("❌ Erro no Laravel:", error.response?.data);
                    io.to(`game-${gameId}`).emit("game-error", "Saldo insuficiente para iniciar o jogo.");
                    game.readyPlayers.clear();
                    return; 
                }
            }

            game.status = "Playing";
            io.to(`game-${gameId}`).emit("game-update", game);
            setGameTimeout(game, handleGameTimeout);
        }
    });

    /**
     * Receção e processamento de uma carta jogada pelo utilizador.
     */
    socket.on("play-card", (payload) => {
        const user = getUser(socket.id);
        const { game, valid, error } = playCard(payload.gameId, user.id, payload.cardId);
        if (!valid) return socket.emit("game-error", error);

        io.to(`game-${game.id}`).emit("game-update", game);
        checkTrickResolution(io, socket, game);

        // Se o jogo for singleplayer e for o turno do bot, processa a reação da IA
        if (game.table.length < 2 && String(game.turnPlayerId) === "bot") {
            processBotTurn(io, socket, game.id);
        }
    });

    /**
     * Cancelamento de um jogo antes de ser iniciado (limpeza de lobby).
     */
    socket.on("cancel-game", (gameId) => {
        const game = getGames().find((g) => g.id === gameId);
        if (game && (game.status === "Pending" || game.status === "Setup")) {
            removeGame(gameId);
            emitLobbyUpdate(io);
        }
    });

    /**
     * Desistência voluntária durante uma partida em curso.
     */
    socket.on("resign-game", (gameID) => {
        const user = getUser(socket.id);
        const game = getGames().find((g) => g.id === gameID);
        if (game) {
            performResignation(io, game, user.id); 
        }
    });
};