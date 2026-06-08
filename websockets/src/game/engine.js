import { createDeck, shuffleDeck, getCardPower } from "./deck.js";
import { setGameTimeout, clearGameTimeout } from "../utils/timer.js";

/**
 * Armazena o estado de todos os jogos ativos em memória.
 */
const games = new Map();

// --- HELPER INTERNO ---
// Decide se deve iniciar o temporizador com base no modo de jogo e no turno atual.
const checkAndStartTimer = (game) => {
    // Se o jogo acabou ou não está em progresso, não inicia timer para evitar execuções órfãs
    if (game.status !== "Playing" && game.status !== "PL") return;

    // --- ALTERAÇÃO CRÍTICA: Restrição ao Multiplayer ---
    // O timer só inicia se o modo for 'multi'. 
    // No Singleplayer, o jogador pode demorar o tempo que quiser sem pressão de tempo.
    if (game.mode === "multi") {
        setGameTimeout(game, handleGameTimeout);
    }
};

/**
 * G3/NF5: Finaliza o jogo por timeout.
 * Esta função é chamada pelo `src/utils/timer.js` quando o tempo acaba.
 */
export const handleGameTimeout = (gameRef) => {
    // 1. Busca a instância "Real" do jogo na memória através do ID de referência
    const game = games.get(gameRef.id);

    if (!game) {
        console.log(`[ENGINE] Timeout ignorado. Jogo ${gameRef.id} já não existe.`);
        return;
    }

    // Dupla verificação de status para garantir que o jogo ainda está ativo no momento do timeout
    if (game.status !== "Playing" && game.status !== "PL") return;

    console.log(`⌛ [ENGINE] Timeout processado para ${game.id}. Turno do Jogador: ${game.turnPlayerId}`);

    // 2. Verifica a injeção de dependência na instância real
    if (typeof game.onTimeout === 'function') {
        // Chama o handler externo (gameHandler.js) para processar a desistência ou penalização
        game.onTimeout(game.id, game.turnPlayerId);
    } else {
        console.error("❌ ERRO CRÍTICO: game.onTimeout não está definido. O jogo ficará bloqueado.");
    }
    
    // Limpa referência interna e cancela o agendamento do temporizador
    clearGameTimeout(game);
};

export const getGames = () => Array.from(games.values());

export const getGame = (gameId) => games.get(gameId);

/**
 * Inicializa um novo jogo.
 */
export const createGame = (type = "3", user, mode = "multi", stake = 3, isMatch = false) => {
    const fullDeck = shuffleDeck(createDeck());
    const trumpCard = fullDeck[fullDeck.length - 1];

    // Define o objeto base com todas as propriedades necessárias para o ciclo de vida do jogo
    const game = {
        id: `game-${Date.now()}`,
        type,
        mode,
        stake,
        isMatch,
        status: "Pending",
        player1: user,
        player2: null,
        deck: fullDeck,
        trumpCard,
        trumpSuit: trumpCard.suit,
        player1Hand: [],
        player2Hand: [],
        table: [],
        player1_points: 0,
        player2_points: 0,
        player1_marks: 0,
        player2_marks: 0,
        turnPlayerId: null,
        winner: null,
        api_id: null,
        stakeTurnPlayerId: null,
        
        // Propriedades técnicas para injeção de dependências e segurança
        systemToken: null, 
        onTimeout: null 
    };

    // Define a propriedade timer como não-enumerável para evitar problemas de serialização via Socket.io/JSON
    Object.defineProperty(game, "timer", {
        value: null,
        writable: true,
        enumerable: false, 
        configurable: true,
    });

    game.turnDeadline = null;

    games.set(game.id, game);
    return game;
};

/**
 * Prepara a próxima ronda (após terminar o baralho ou match point).
 */
export const startNextRound = (gameId) => {
    const game = games.get(gameId);
    if (!game) return null;

    // Reseta o baralho, trunfo e pontuações internas para o início de uma nova mão
    const fullDeck = shuffleDeck(createDeck());
    game.deck = fullDeck;
    game.trumpCard = fullDeck[fullDeck.length - 1];
    game.trumpSuit = game.trumpCard.suit;
    game.player1_points = 0;
    game.player2_points = 0;
    game.table = [];
    game.status = "Playing";

    // Distribui a mão inicial baseada no tipo de jogo (Bisca 3 ou Bisca 9)
    const handSize = game.type === "9" ? 9 : 3;
    game.player1Hand = game.deck.splice(0, handSize);
    game.player2Hand = game.deck.splice(0, handSize);

    // Garante que existe um jogador definido para iniciar a ronda
    if (!game.turnPlayerId) {
        game.turnPlayerId = game.player1.id;
    }

    // Reinicia o timer para a nova ronda (respeitando a lógica multi/single)
    checkAndStartTimer(game);

    return game;
};

/**
 * Player 2 entra no jogo.
 */
export const joinGame = (gameId, user) => {
    const game = games.get(gameId);
    if (!game || game.status !== "Pending") return null;

    game.player2 = user;
    
    // Altera o estado para Setup (se for partida longa) ou começa imediatamente o jogo
    game.status = game.isMatch ? "Setup" : "Playing";

    const handSize = game.type === "9" ? 9 : 3;
    game.player1Hand = game.deck.splice(0, handSize);
    game.player2Hand = game.deck.splice(0, handSize);

    // Define quem começa (Player 1 sempre começa no arranque inicial)
    game.turnPlayerId = game.player1.id;

    // Se o jogo começar imediatamente (Single Game), inicia o timer de turno
    if (game.status === "Playing") {
        checkAndStartTimer(game);
    }

    return game;
};

/**
 * Executa uma jogada.
 */
export const playCard = (gameId, userId, cardId) => {
    const game = games.get(gameId);

    // Validação de segurança: verifica se o jogo existe e se está em estado de jogo
    if (!game || (game.status !== "Playing" && game.status !== "PL")) {
        return { game, valid: false, error: "O jogo não está ativo." };
    }
    
    // Validação de turno: impede que um jogador jogue fora da sua vez
    if (String(game.turnPlayerId) !== String(userId)) {
        return { game, valid: false, error: "Aguarde o seu turno." };
    }

    const isP1 = String(userId) === String(game.player1.id);
    const hand = isP1 ? game.player1Hand : game.player2Hand;

    // Localiza a carta na mão do jogador
    const cardIndex = hand.findIndex((c) => String(c.id) === String(cardId));
    if (cardIndex === -1) return { game, valid: false, error: "Carta inválida." };

    const playedCard = hand[cardIndex];

    // Regra de Renúncia: No final do deck, o jogador é obrigado a seguir o naipe inicial da vaza
    if (game.deck.length === 0 && game.table.length === 1) {
        const leadCard = game.table[0].card;
        if (playedCard.suit !== leadCard.suit && hand.some((c) => c.suit === leadCard.suit)) {
            return { game, valid: false, error: "Regra: Deve assistir ao naipe!" };
        }
    }

    // --- Executa a jogada ---
    
    // 1. Interrompe o timer assim que a jogada válida é recebida
    clearGameTimeout(game);

    // 2. Transfere a carta da mão para o centro da mesa
    hand.splice(cardIndex, 1);
    game.table.push({ playerId: userId, card: playedCard });
    
    // 3. Determina o próximo passo: alternar turno ou aguardar resolução da vaza
    if (game.table.length === 1) {
        game.turnPlayerId = isP1 ? game.player2.id : game.player1.id;
        
        // Inicia timer para o próximo jogador reagir à carta jogada
        checkAndStartTimer(game);
    } else {
        // Vaza está completa e pronta para ser resolvida pelo motor
        game.turnPlayerId = null;
    }

    return { game, valid: true };
};

/**
 * Resolve a vaza.
 */
export const resolveTrick = (gameId) => {
    const game = games.get(gameId);
    if (!game || game.table.length !== 2) return null;

    const [t1, t2] = game.table;
    let trickWinnerId = t1.playerId;

    // Lógica de comparação de cartas para determinar o vencedor da vaza
    if (t1.card.suit === t2.card.suit) {
        // Se os naipes são iguais, vence quem tiver maior poder (rank)
        if (getCardPower(t2.card.rank) > getCardPower(t1.card.rank)) {
            trickWinnerId = t2.playerId;
        }
    } else if (t2.card.suit === game.trumpSuit) {
        // Se os naipes são diferentes, o jogador 2 só ganha se jogar um trunfo (corte)
        trickWinnerId = t2.playerId;
    }

    // Acumula os pontos da vaza para o vencedor correspondente
    const trickPoints = t1.card.value + t2.card.value;
    if (String(trickWinnerId) === String(game.player1.id)) {
        game.player1_points += trickPoints;
    } else {
        game.player2_points += trickPoints;
    }

    // Distribui novas cartas do deck para os jogadores, começando pelo vencedor da vaza
    if (game.deck.length > 0) {
        const isWinnerP1 = String(trickWinnerId) === String(game.player1.id);
        const pWinnerHand = isWinnerP1 ? game.player1Hand : game.player2Hand;
        const pLoserHand  = isWinnerP1 ? game.player2Hand : game.player1Hand;

        pWinnerHand.push(game.deck.shift());
        if (game.deck.length > 0) {
            pLoserHand.push(game.deck.shift());
        }
    }

    // Limpa a mesa e define que o vencedor da última vaza começa a próxima
    game.table = [];
    game.turnPlayerId = trickWinnerId;

    // Verifica se a ronda ou o jogo completo chegou ao fim (mãos e deck vazios)
    const handEmpty = game.player1Hand.length === 0 && game.player2Hand.length === 0;
    
    if (game.deck.length === 0 && handEmpty) {
        // Atribui as "marcas" (pontos de vitória) baseadas na pontuação da ronda
        if (game.player1_points > 60) {
            const marks = game.player1_points === 120 ? 4 : game.player1_points >= 91 ? 2 : 1;
            game.player1_marks += marks;
        } else if (game.player2_points > 60) {
            const marks = game.player2_points === 120 ? 4 : game.player2_points >= 91 ? 2 : 1;
            game.player2_marks += marks;
        }

        // Determina se o jogo acabou ou se deve haver uma nova ronda (Match vs Single)
        const isMatchOver = game.isMatch ? (game.player1_marks >= 4 || game.player2_marks >= 4) : true;

        if (isMatchOver) {
            game.status = "Ended";
            clearGameTimeout(game); 
            
            // Lógica de desempate e definição do vencedor final
            if (game.player1_marks > game.player2_marks) game.winner = game.player1.id;
            else if (game.player2_marks > game.player1_marks) game.winner = game.player2.id;
            else if (!game.isMatch && game.player1_points > game.player2_points) game.winner = game.player1.id;
            else if (!game.isMatch && game.player2_points > game.player1_points) game.winner = game.player2.id;
            else game.winner = "Draw";

        } else {
            // Prepara estado para transição de ronda
            game.status = "RoundEnded"; 
            clearGameTimeout(game);
        }
    } else {
        // Jogo continua: Reinicia o timer para o jogador que ganhou a vaza e deve abrir a próxima
        checkAndStartTimer(game);
    }

    return game;
};

/**
 * Remove o jogo da memória e limpa qualquer timer pendente para evitar fugas de memória.
 */
export const removeGame = (gameId) => {
    const game = games.get(gameId);
    if (game) {
        clearGameTimeout(game);
    }
    return games.delete(gameId);
};