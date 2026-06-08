import { getCardPower } from "./deck.js";

/**
 * G3: Calcula a jogada do Bot de acordo com as regras oficiais da Bisca.
 * Estratégia:
 * 1. Se joga primeiro: Lança a carta mais baixa (preservar trunfos/pontos).
 * 2. Se joga em segundo: Tenta ganhar a vaza com a carta mínima necessária.
 * 3. Se não consegue ganhar: Lança a carta mais baixa ("lixo").
 * * @param {Object} game - O estado atual do jogo
 * @returns {Object} A carta escolhida para jogar
 */
export const getBotMove = (game) => {
    // Define a mão do bot e o trunfo atual para processamento da lógica
    const hand = game.player2Hand;
    const trumpSuit = game.trumpSuit;

    // Garante que o bot possui cartas para jogar antes de prosseguir
    if (!hand || hand.length === 0) return null;

    // Identifica se já existe uma carta jogada pelo adversário para decidir a estratégia de resposta
    const tableCard = game.table.length > 0 ? game.table[0].card : null;

    // --- CENÁRIO 1: BOT JOGA PRIMEIRO ---
    if (!tableCard) {
        // Tenta abrir o jogo com a carta de menor valor para não entregar pontos ou gastar trunfos cedo
        return getLowestCard(hand, trumpSuit);
    }

    // --- CENÁRIO 2: BOT JOGA EM SEGUNDO ---
    const leadSuit = tableCard.suit;
    const isTableTrump = leadSuit === trumpSuit;
    const tablePower = getCardPower(tableCard.rank);

    /**
     * 1. Determinar cartas legalmente jogáveis (Regra de Assistir/NF)
     * Restringe as opções de jogada caso o baralho esteja vazio, seguindo as regras de obrigatoriedade de naipe.
     */
    let playableCards = hand;
    if (game.deck.length === 0) {
        const cardsOfLeadSuit = hand.filter(c => c.suit === leadSuit);
        if (cardsOfLeadSuit.length > 0) {
            playableCards = cardsOfLeadSuit;
        }
    }

    /**
     * 2. Tentar encontrar cartas que ganham a vaza
     * Filtra entre as cartas jogáveis aquelas que possuem poder suficiente para superar a carta na mesa.
     */
    const winningCards = playableCards.filter(card => {
        const isCardTrump = card.suit === trumpSuit;
        const isSameSuit = card.suit === leadSuit;

        if (isTableTrump) {
            // Se a mesa tem trunfo, o bot só pode ganhar se possuir um trunfo com ranking superior
            return isCardTrump && getCardPower(card.rank) > tablePower;
        } else {
            // Se a mesa não tem trunfo, o bot ganha cortando com qualquer trunfo ou jogando um rank maior do mesmo naipe
            if (isCardTrump) return true;
            if (isSameSuit) return getCardPower(card.rank) > tablePower;
        }
        return false;
    });

    if (winningCards.length > 0) {
        // Seleciona a carta vencedora menos valiosa para ganhar a vaza com o menor investimento possível
        return getLowestCard(winningCards, trumpSuit);
    }

    /**
     * 3. Se não consegue ganhar, "descarta" a carta mais fraca
     * Quando a vitória na vaza é impossível, minimiza o prejuízo descartando a carta de menor valor.
     */
    return getLowestCard(playableCards, trumpSuit);
};

/**
 * Auxiliar: Retorna a carta com menor valor (pontos).
 * Critério de desempate: Menor poder (rank) e evitar gastar trunfos desnecessariamente.
 */
const getLowestCard = (cards, trumpSuit) => {
    // Proteção contra listas vazias na seleção de menor carta
    if (!cards || cards.length === 0) return null;

    return cards.reduce((prev, curr) => {
        const valPrev = prev.value || 0;
        const valCurr = curr.value || 0;

        // Prioriza manter trunfos na mão, tratando-os como cartas de "alto valor" estratégico
        if (prev.suit === trumpSuit && curr.suit !== trumpSuit) return curr;
        if (prev.suit !== trumpSuit && curr.suit === trumpSuit) return prev;

        // Compara o valor pontual das cartas para decidir qual é menos importante (ex: 2 valendo menos que Ás)
        if (valPrev < valCurr) return prev;
        if (valCurr < valPrev) return curr;

        // Utiliza o ranking de força (Bisca) como critério final para diferenciar cartas de mesmo valor pontual
        return getCardPower(prev.rank) <= getCardPower(curr.rank) ? prev : curr;
    });
};