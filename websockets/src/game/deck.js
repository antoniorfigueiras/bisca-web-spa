/**
 * Definição das regras base do baralho de Bisca (40 cartas).
 */

// Mapeamento para os nomes dos ficheiros de imagem (ex: c1.png, p11.png)
// Garante a correspondência entre os nomes dos naipes e os prefixos dos assets visuais
const SUIT_MAP = {
    "Copas": "c",
    "Ouros": "o",
    "Espadas": "e",
    "Paus": "p"
};

// Converte os nomes dos Ranks para os valores numéricos usados na nomenclatura dos ficheiros
const RANK_MAP = {
    "A": "1",
    "7": "7",
    "K": "13",
    "J": "11",
    "Q": "12",
    "6": "6",
    "5": "5",
    "4": "4",
    "3": "3",
    "2": "2"
};

export const SUITS = ["Copas", "Ouros", "Espadas", "Paus"];

/**
 * Hierarquia oficial de poder (índice maior = ganha vaza):
 * 2, 3, 4, 5, 6, Q, J, K, 7, A
 */
export const RANKS = ["2", "3", "4", "5", "6", "Q", "J", "K", "7", "A"];

/**
 * Valor em pontos de cada carta para a contagem final (total 120 pontos)
 * Define o peso matemático de cada carta para o cálculo da pontuação final da partida
 */
export const VALUES = {
    "A": 11,
    "7": 10,
    "K": 4,
    "J": 3,
    "Q": 2
};

/**
 * Cria um novo baralho de 40 cartas com IDs compatíveis com o frontend.
 * Gera os objetos de carta combinando naipes e ranks, atribuindo propriedades de jogo e visuais
 */
export const createDeck = () => {
    const deck = [];

    // Itera sobre naipes e ranks para construir todas as combinações do baralho de 40 cartas
    SUITS.forEach(suit => {
        RANKS.forEach(rank => {
            const suitCode = SUIT_MAP[suit];
            const rankCode = RANK_MAP[rank];
            const cardCode = `${suitCode}${rankCode}`;

            deck.push({
                id: cardCode,       // Identificador único para chaves em listas (ex: 'c1')
                code: cardCode,     // Referência direta para o nome do ficheiro de imagem
                rank: rank,         // Representação textual do valor da carta
                suit: suit,         // Nome completo do naipe
                value: VALUES[rank] || 0, // Pontos que a carta vale no final do jogo
                power: RANKS.indexOf(rank) // Peso numérico para determinar quem ganha a vaza
            });
        });
    });

    return deck;
};

/**
 * Baralha as cartas usando o algoritmo Fisher-Yates.
 * Garante uma distribuição aleatória e uniforme das cartas no início do jogo
 */
export const shuffleDeck = (deck) => {
    const shuffled = [...deck];
    // Percorre o array de trás para a frente trocando elementos aleatoriamente
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
};

/**
 * Retorna o peso de uma carta para comparação de vazas.
 * Permite extrair a força de uma carta apenas com base no seu rank (Ex: 'A' > '7')
 */
export const getCardPower = (rank) => {
    const power = RANKS.indexOf(String(rank));
    // Retorna 0 como fallback caso o rank não seja encontrado na hierarquia oficial
    return power === -1 ? 0 : power;
};