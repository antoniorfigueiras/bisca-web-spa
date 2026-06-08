import { io } from 'socket.io-client'
import { useAuthStore } from '@/stores/auth'
import { useGameStore } from '@/stores/game'
import { useToastStore } from '@/stores/toast'

const SERVER_URL = import.meta.env.VITE_WS_URL || 'http://localhost:8080'

/**
 * G3/G4: Serviço central de WebSockets para comunicação em tempo real.
 * Atua como o meio de transmissão para o servidor, que é a "fonte da verdade" .
 */
class SocketService {
  constructor() {
    this.socket = null
    this.authStore = null
    this.gameStore = null
    this.toastStore = null
  }

  // --- 1. INICIALIZAÇÃO E LISTENERS (NF9) ---

  /**
   * Inicializa a ligação e configura os listeners para eventos distribuídos.
   */
  init() {
    this.authStore = useAuthStore()
    this.gameStore = useGameStore()
    this.toastStore = useToastStore()

    if (this.socket) return

    // NF7: Recupera o token para autenticação segura no handshake do Socket.io.
    const token = localStorage.getItem('token') || null

    this.socket = io(SERVER_URL, {
      transports: ['websocket'], // NF8: Força o uso de WebSockets para performance.
      reconnectionAttempts: 10,
      autoConnect: true,
      auth: {
        token: token
      }
    })

    // --- EVENTOS DE LIGAÇÃO ---

    this.socket.on('connect', () => {
      console.log('🔌 Ligado ao servidor:', this.socket.id)
      this.identifyUser()
      this.emitGetGames()
    })

    this.socket.on('reconnect', () => {
      console.log('🔄 Relidago ao servidor')
      this.identifyUser()
    })

    this.socket.on('connect_error', (error) => {
      console.error('❌ Erro de ligação:', error.message)
      if (error.message === 'unauthorized') {
        this.toastStore.showError('Sessão expirada. Por favor, faça login novamente.')
      }
    })

    // --- LOBBY & SALAS (G3) ---

    this.socket.on('games', (list) => {
      this.gameStore.multiplayerGames = list
    })

    this.socket.on('game-created', (game) => {
      this.gameStore.activeMultiplayerGame = game
    })

    // --- CICLO DE JOGO (G3/G4) ---

    /**
     * G3: Inicia a partida e define se é contra Bot ou Humano.
     */
    this.socket.on('game-start', (game) => {
      this.gameStore.updateGame(game)
      const isSingle = game.mode === 'single'
      console.log(isSingle ? '🎮 Jogo Singleplayer iniciado!' : '⚔️ Jogo Multiplayer iniciado!')
    })

    /**
     * G3: Atualiza o estado da mesa, incluindo cartas jogadas e turnos.
     */
    this.socket.on('game-update', (game) => {
      if (this.gameStore.activeMultiplayerGame?.id === game.id) {
        // NF8: Atualiza a Store para refletir o tempo restante de jogada.
        this.gameStore.updateGame(game)

        if (!game.pendingStake) {
          this.gameStore.incomingStakeProposal = null
        }
      }
    })

    /**
     * G3: Recebe propostas de aumento de aposta em tempo real.
     */
    this.socket.on('stake-proposal', (data) => {
      this.gameStore.incomingStakeProposal = data
      this.toastStore.showInfo(`${data.proposer} propôs aumentar a aposta para ${data.amount}!`)
    })

    /**
     * G4: Evento de fim de jogo. Processa resultados e marks.
     */
    this.socket.on('game-over', (game) => {
      if (this.gameStore.activeMultiplayerGame?.id !== game.id) return
      this.gameStore.handleGameEnd(game)
    })

    this.socket.on('game-error', (msg) => {
      this.toastStore.showError(msg)
    })
  }

  // --- 2. MÉTODOS DE EMISSÃO (SENDERS) ---

  /**
   * NF7: Identifica o utilizador no servidor para associar a jogos multiplayer.
   */
  identifyUser() {
    const user = this.authStore?.user
    const payload = user
      ? { id: user.id, nickname: user.nickname, type: user.type }
      : { id: `anon-${this.socket.id}`, nickname: 'Convidado', type: 'P' }

    this.socket.emit('join', payload)
  }

  emitGetGames() {
    this.socket?.emit('get-games')
  }

  /**
   * G3: Cria uma nova instância de jogo ou partida.
   */
  createGame(options) {
    if (!this.socket?.connected) {
        this.toastStore.showError('Sem ligação ao servidor de jogo.')
        return
    }

    const payload = {
      ...options,
      mode: options.mode || 'multi',
      isMatch: !!options.isMatch,
      stake: options.isMatch ? (options.stake || 3) : 2,
    }
    this.socket.emit('create-game', payload)
  }

  joinGame(gameId) {
    if (!this.socket?.connected) return
    this.socket.emit('join-game', gameId)
  }

  /**
   * G3: Envia proposta de aumento de aposta mútua.
   */
  proposeStake(gameId, amount) {
    this.socket?.emit('propose-stake', { gameId, amount })
  }

  answerProposal(gameId, accepted) {
    this.socket?.emit('answer-stake-proposal', { gameId, accepted })
  }

  emitReady(gameId) {
    this.socket?.emit('player-ready', { gameId })
  }

  /**
   * G3: Notifica o servidor da carta jogada para resolução de vaza.
   */
  playCard(gameId, cardId) {
    this.socket?.emit('play-card', { gameId, cardId })
  }

  cancelGame(gameId) {
    if (this.socket?.connected) {
      this.socket.emit('cancel-game', gameId);
    }
  }

  /**
   * G3: Desistir do jogo. O oponente recebe as cartas restantes.
   */
  resignGame(gameId) {
    if (this.socket?.connected) {
        this.socket.emit('resign-game', gameId)
    }
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect()
      this.socket = null
    }
  }
}

export default new SocketService()
