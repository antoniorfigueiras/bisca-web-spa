import { createGame, getGame, initializeBoard } from "./game.js";

const matches = new Map();

// --- REGRAS DE PONTUAÇÃO ---
const calculateMarks = (winnerScore, loserScore, reason) => {
  // 🔥 Se foi desistência, o vencedor ganha 4 marcas (Bandeira/Vitória total do jogo)
  // Podes mudar para 2 se preferires apenas um "Capote"
  if (reason === "Resignation") return 4;

  if (winnerScore === 120) return 4; // BANDEIRA
  if (winnerScore > 90) return 2; // CAPOTE
  return 1; // SIMPLES
};

export const createMatch = (matchIDFromDB, p1, p2, difficulty) => {
  const match = {
    id: matchIDFromDB,
    player1: p1,
    player2: p2,
    difficulty: difficulty,
    status: "playing",
    p1Marks: 0,
    p2Marks: 0,
    gamesHistory: [],
    currentGameId: null,
    dealer: p1.id,
  };
  matches.set(match.id, match);
  return match;
};

export const startNewGameInMatch = (
  matchID,
  externalGameID,
  oldGame = null
) => {
  let match = matches.get(matchID);

  // Auto-Recuperação
  if (!match && oldGame) {
    match = createMatch(
      matchID,
      oldGame.player1,
      oldGame.player2,
      oldGame.difficulty
    );
    const lastDealer = oldGame.dealer || oldGame.player1.id;
    match.dealer =
      String(lastDealer) === String(match.player1.id)
        ? match.player2.id
        : match.player1.id;
  }

  if (!match) return null;

  const newGame = createGame(match.difficulty, match.player1, externalGameID);

  newGame.player2 = match.player2;
  newGame.status = "playing";
  newGame.matchId = matchID;
  newGame.dealer = match.dealer;

  initializeBoard(newGame);

  match.currentGameId = newGame.id;
  match.gamesHistory.push(newGame.id);

  return newGame;
};

export const getMatch = (matchID) => matches.get(matchID);

// 🔥 ATUALIZADO: Aceita 'forcedWinnerId' e 'reason'
export const processGameEndForMatch = (
  game,
  io,
  forcedWinnerId = null,
  reason = "Normal"
) => {
  if (!game.matchId) return false;

  let match = matches.get(game.matchId);
  if (!match) {
    // Recuperação de emergência
    match = createMatch(
      game.matchId,
      game.player1,
      game.player2,
      game.difficulty
    );
  }

  let winnerId = forcedWinnerId;
  let marksToAdd = 0;

  // 1. Determinar Vencedor
  if (!winnerId) {
    if (game.player1Score > game.player2Score) winnerId = game.player1.id;
    else if (game.player2Score > game.player1Score) winnerId = game.player2.id;
  }

  // 2. Calcular Marcas
  if (winnerId) {
    // Pontuações para cálculo
    const p1Score = game.player1Score;
    const p2Score = game.player2Score;

    // Se P1 ganhou
    if (String(winnerId) === String(match.player1.id)) {
      // Se foi desistência, o P1 ganha marcas máximas (ou conforme a regra acima)
      marksToAdd = calculateMarks(p1Score, p2Score, reason);
      match.p1Marks += marksToAdd;
    }
    // Se P2 ganhou
    else {
      marksToAdd = calculateMarks(p2Score, p1Score, reason);
      match.p2Marks += marksToAdd;
    }
  }

  // 3. Verificar Vencedor da Partida (4 Marcas)
  let matchWinner = null;
  if (match.p1Marks >= 4) matchWinner = match.player1;
  else if (match.p2Marks >= 4) matchWinner = match.player2;

  console.log(
    `[Match] Jogo terminou (${reason}). +${marksToAdd} marcas. Total: ${match.p1Marks}-${match.p2Marks}`
  );

  // 4. Emitir Update para o Frontend
  io.to(`game-${game.id}`).emit("match-update", {
    gameWinner: winnerId,
    marksAdded: marksToAdd,
    p1TotalMarks: match.p1Marks,
    p2TotalMarks: match.p2Marks,
    matchWinner: matchWinner,
    nextGameIn: 3, // informativo
  });

  // 5. Rotação do Dealer
  if (!matchWinner) {
    match.dealer =
      String(match.dealer) === String(match.player1.id)
        ? match.player2.id
        : match.player1.id;
  } else {
    match.status = "finished";
  }

  return true;
};
