import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import socketService from '@/services/socket'

/**
 * G3/G4: Store principal para gestão do estado de jogo em tempo real.
 * Centraliza os dados recebidos via WebSocket e fornece propriedades computadas para a interface (NF9).
 */
export const useGameStore = defineStore('game', () => {
  const authStore = useAuthStore()

  // --- ESTADO (STATE) ---
  const multiplayerGames = ref([])        // Lista de salas disponíveis no Lobby
  const activeMultiplayerGame = ref(null) // Dados do jogo ou partida em curso
  const gameResult = ref(null)            // Resumo gerado após a conclusão do jogo
  const incomingStakeProposal = ref(null) // Registo de proposta de aposta pendente
  const isLoading = ref(false)            // Controlo de feedback visual de carregamento (NF5)

  /**
   * G3: Identificador do jogador com direito de propor aumento de aposta.
   * Garante a alternância e ordem nas negociações de moedas.
   */
  const stakeTurnPlayerId = ref(null)

  // --- AUXILIARES (HELPERS) ---

  /**
   * Resolve a identidade do utilizador local para comparação com dados do servidor (NF7).
   * Suporta utilizadores autenticados e convidados anónimos.
   */
  const currentUserId = computed(() => {
    if (authStore.user?.id) return authStore.user.id
    if (socketService.socket?.id) return `anon-${socketService.socket.id}`
    return null
  })

  // --- GETTERS (PROPRIEDADES COMPUTADAS) ---

  /**
   * Determina se o utilizador local é o anfitrião/criador da partida.
   */
  const isPlayer1 = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game || !currentUserId.value) return false

    const p1Id = game.player1?.id || game.player1_user_id || game.player1
    return String(p1Id) === String(currentUserId.value)
  })

  /**
   * Retorna o perfil do oponente para exibição na mesa de jogo.
   */
  const opponent = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game) return null
    return isPlayer1.value ? game.player2 : game.player1
  })

  /**
   * G3: Verifica a validade do turno de aposta conforme as regras de negócio.
   */
  const canIProposeStake = computed(() => {
    const turnId = stakeTurnPlayerId.value || activeMultiplayerGame.value?.stakeTurnPlayerId
    if (!turnId) return false
    return String(turnId) === String(currentUserId.value)
  })

  /**
   * G3: Cartas do utilizador local, garantindo que o segredo do jogo é mantido.
   */
  const myHand = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game) return []
    return isPlayer1.value ? game.player1Hand : game.player2Hand
  })

  /**
   * Retorna apenas a quantidade de cartas do adversário (NF9).
   */
  const opponentHandCount = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game) return 0
    const oppHand = isPlayer1.value ? game.player2Hand : game.player1Hand
    return oppHand ? oppHand.length : 0
  })

  /**
   * G3: Indica se o servidor aguarda a jogada do utilizador local.
   */
  const isMyTurn = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game || !currentUserId.value) return false
    const turnId = game.turnPlayerId
    return String(turnId) === String(currentUserId.value)
  })

  /**
   * NF5: Timestamp limite para a jogada, utilizado pelo TurnTimer.vue.
   */
  const turnDeadline = computed(() => {
    return activeMultiplayerGame.value?.turnDeadline || null
  })

  // Elementos visuais da mesa de jogo
  const tableCards = computed(() => activeMultiplayerGame.value?.table || [])
  const trumpCard = computed(() => activeMultiplayerGame.value?.trumpCard || null)
  const trumpSuit = computed(() => activeMultiplayerGame.value?.trumpSuit || '')

  const deckCount = computed(() => {
    const deck = activeMultiplayerGame.value?.deck
    if (Array.isArray(deck)) return deck.length
    return activeMultiplayerGame.value?.deckCount || 0
  })

  /**
   * G3: Pontuação parcial da ronda.
   */
  const myScore = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game) return 0
    return isPlayer1.value ? game.player1_points || 0 : game.player2_points || 0
  })

  const opponentScore = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game) return 0
    return isPlayer1.value ? game.player2_points || 0 : game.player1_points || 0
  })

  /**
   * G3: Estado da partida (série de jogos) e marcas acumuladas.
   */
  const isMatch = computed(() => !!activeMultiplayerGame.value?.isMatch)

  const myMarks = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game || !isMatch.value) return 0
    return isPlayer1.value ? game.player1_marks || 0 : game.player2_marks || 0
  })

  const opponentMarks = computed(() => {
    const game = activeMultiplayerGame.value
    if (!game || !isMatch.value) return 0
    return isPlayer1.value ? game.player2_marks || 0 : game.player1_marks || 0
  })

  // --- AÇÕES (ACTIONS) ---

  /**
   * NF9: Sincroniza o estado local com os eventos WebSocket do servidor.
   */
  const updateGame = (gameData) => {
    activeMultiplayerGame.value = gameData
    if (gameData.stakeTurnPlayerId) {
      stakeTurnPlayerId.value = gameData.stakeTurnPlayerId
    }
  }

  /**
   * G4: Finaliza o jogo e prepara o relatório de resultado para o utilizador.
   * Atualiza o saldo de moedas do jogador após a resolução da aposta.
   */
  const handleGameEnd = (endedGame) => {
    if (!currentUserId.value || !activeMultiplayerGame.value) return

    activeMultiplayerGame.value = endedGame

    const myId = currentUserId.value
    const isWinner = String(endedGame.winner) === String(myId)
    const wasIPlayer1 = isPlayer1.value

    let type = 'Fim da Partida'

    // NF5: Define mensagens de feedback baseadas nas regras de desistência ou tempo .
    if (endedGame.endReason === 'Resignation/Timeout' || endedGame.endReason === 'timeout') {
      type = isWinner ? 'VITÓRIA POR DESISTÊNCIA ⚡' : 'DERROTA POR TEMPO ⌛'
    } else if (isMatch.value) {
        const p1m = endedGame.player1_marks || 0
        const p2m = endedGame.player2_marks || 0
        // Verifica se a partida terminou por limite de marcas
        if (p1m >= 4 || p2m >= 4) {
             type = isWinner ? 'VITÓRIA NA PARTIDA! 🏆' : 'DERROTA NA PARTIDA ❌'
        } else {
             type = isWinner ? 'VITÓRIA NO JOGO' : 'DERROTA NO JOGO'
        }
    } else {
        type = isWinner ? 'VITÓRIA!' : 'DERROTA'
    }

    if (endedGame.winner === 'Draw') type = 'EMPATE 🤝'

    // G4: Objeto de resumo para persistência e exibição no modal final
    gameResult.value = {
      isWinner,
      isDraw: endedGame.winner === 'Draw',
      myPoints: wasIPlayer1 ? endedGame.player1_points : endedGame.player2_points,
      opPoints: wasIPlayer1 ? endedGame.player2_points : endedGame.player1_points,
      myMarks: wasIPlayer1 ? endedGame.player1_marks : endedGame.player2_marks,
      opMarks: wasIPlayer1 ? endedGame.player1_marks : endedGame.player2_marks,
      type,
      reason: endedGame.endReason || 'Normal',
    }

    activeMultiplayerGame.value.status = 'Ended'

    // G2: Sincroniza o saldo de moedas com a base de dados após o prémio/perda.
    if (authStore.user) {
      authStore.refreshUser()
    }
  }

  /**
   * Limpa o estado da store ao sair da mesa de jogo (NF1).
   */
  const resetState = () => {
    activeMultiplayerGame.value = null
    gameResult.value = null
    incomingStakeProposal.value = null
    isLoading.value = false
    stakeTurnPlayerId.value = null
  }

  return {
    // State
    multiplayerGames,
    activeMultiplayerGame,
    gameResult,
    incomingStakeProposal,
    isLoading,
    stakeTurnPlayerId,
    // Getters
    currentUserId,
    isPlayer1,
    opponent,
    canIProposeStake,
    myHand,
    opponentHandCount,
    tableCards,
    isMyTurn,
    turnDeadline,
    trumpCard,
    trumpSuit,
    deckCount,
    myScore,
    opponentScore,
    isMatch,
    myMarks,
    opponentMarks,
    // Actions
    updateGame,
    handleGameEnd,
    resetState,
  }
})
