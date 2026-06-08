/**
 * Gestão de Conexões e Utilizadores Online.
 */
import { getGames, removeGame, handleGameTimeout } from "../game/engine.js";

const users = new Map();

/**
 * G1: Adiciona o utilizador à memória do servidor.
 * CORREÇÃO: Removemos o Number() forçado para suportar IDs anónimos "anon-..."
 */
export const addUser = (socketId, user) => {
    // Se for um número (utilizador logado), convertemos para garantir consistência. 
    // Se for string (anónimo), mantemos como está para suportar prefixos de texto.
    const processedId = isNaN(user.id) ? user.id : Number(user.id);

    users.set(socketId, {
        id: processedId,
        name: user.name || "Convidado",
        nickname: user.nickname || "Convidado",
        type: user.type, // 'P' para Player, 'A' para Admin, undefined para Anónimo
        isBot: false,
    });
};

/**
 * Remove o utilizador do mapa de utilizadores ativos e retorna os seus dados antes da eliminação.
 */
export const removeUser = (socketId) => {
    const user = users.get(socketId);
    if (user) {
        users.delete(socketId);
        return user;
    }
    return null;
};

export const getUser = (socketId) => users.get(socketId);
export const getUserCount = () => users.size;

/**
 * Inicializa a gestão de ciclo de vida das conexões via Socket.io.
 */
export const handleConnection = (io, socket) => {
  
    /**
     * Evento acionado quando um cliente se regista no servidor.
     */
    socket.on("join", (user) => {
        // Validação básica: impede o registo se o utilizador não fornecer um ID
        if (!user?.id) return;

        // NF1: Verificação de segurança para impedir o acesso de utilizadores banidos ou suspensos
        if (user.blocked) {
            socket.emit("game-error", "A sua conta está bloqueada.");
            return socket.disconnect();
        }

        addUser(socket.id, user);
        
        // Log de depuração para rastrear entradas de jogadores no sistema
        const currentUser = getUser(socket.id);
        console.log(
            `[CONN] Utilizador registado: ${currentUser.nickname} (ID: ${currentUser.id})`
        );

        // Atualiza o cliente com a lista de jogos disponíveis no Lobby (exclui jogos privados/singleplayer)
        const lobbyGames = getGames().filter(
            (g) => (g.status === "Pending" || g.status === "PE") && g.mode !== "single"
        );
        socket.emit("games", lobbyGames);
    });

    /**
     * Evento acionado quando a ligação do socket é interrompida.
     * Trata a limpeza de jogos ativos e penalizações por desistência.
     */
    socket.on("disconnect", (reason) => {
        const user = removeUser(socket.id);

        if (user) {
            console.log(`[CONN] Sessão encerrada para: ${user.nickname} (Razão: ${reason})`);

            const allGames = getGames();
            allGames.forEach((game) => {
                // Identifica se o utilizador que desconectou fazia parte de algum jogo ativo
                const isPlayer1 = String(game.player1?.id) === String(user.id);
                const isPlayer2 = String(game.player2?.id) === String(user.id);

                if (isPlayer1 || isPlayer2) {
                    // Se o jogo estava em espera ou era singleplayer, removemos silenciosamente
                    if (game.mode === "single" || game.status === "Pending" || game.status === "PE") {
                        removeGame(game.id);
                    } 
                    // Se o jogo estava em curso, força um timeout para declarar vitória ao adversário
                    else if (game.status === "Playing" || game.status === "PL") {
                        game.turnPlayerId = user.id; // Garante que o timeout é atribuído a quem saiu
                        handleGameTimeout(game);
                        io.to(`game-${game.id}`).emit("game-over", game);

                        // Mantém o jogo em memória brevemente para permitir o envio final de dados
                        setTimeout(() => {
                            removeGame(game.id);
                        }, 1000);
                    }
                }
            });

            // Notifica todos os utilizadores no Lobby sobre a alteração na lista de jogos
            io.emit(
                "games",
                getGames().filter((g) => (g.status === "Pending" || g.status === "PE") && g.mode !== "single")
            );
        }
    });
};