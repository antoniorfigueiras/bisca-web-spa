<template>
  <div class="h-[calc(100vh-64px)] bg-slate-900 font-sans text-slate-100 flex flex-col relative overflow-hidden">

    <div v-if="isLoading || !effectiveUserID" class="flex-1 flex flex-col items-center justify-center bg-slate-950 z-50">
      <div class="relative w-20 h-20">
        <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20"></div>
        <div class="absolute inset-0 rounded-full border-4 border-t-indigo-500 animate-spin"></div>
      </div>
      <p class="mt-6 text-indigo-400 font-black uppercase text-[10px] tracking-[0.2em] animate-pulse">Sincronizando Identidade...</p>
    </div>

    <div v-else-if="gameStatus === 'pending'" class="flex-1 flex flex-col items-center justify-center p-6 relative">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,var(--tw-gradient-stops))] from-indigo-900/20 via-slate-900 to-slate-950 -z-10"></div>
      <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl p-10 text-center relative border border-white/10">
        <div class="mx-auto bg-indigo-50 w-24 h-24 rounded-3xl flex items-center justify-center mb-8 rotate-3 shadow-inner">
          <span class="text-5xl animate-bounce">⏳</span>
        </div>
        <h2 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">À espera de um craque...</h2>
        <button @click="quitGameAndGoLobby" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold uppercase tracking-widest text-xs">Cancelar Procura</button>
      </div>
    </div>

    <div v-else-if="['playing', 'setup', 'ended', 'intermission'].includes(gameStatus)" class="flex-1 relative w-full h-full flex flex-col">

      <GameBoard
        :game="game"
        :myID="effectiveUserID"
        :opponentName="opponentName"
        :isPaused="gameStatus === 'setup' || gameStatus === 'intermission'"
        @play-card="handlePlayCard"
        @resign="handleResign"
        class="flex-1 transition-all duration-500"
        :class="{ 'blur-sm scale-95 opacity-50': gameStatus === 'intermission' }"
      />

      <Transition name="fade">
        <div v-if="gameStatus === 'intermission'" class="absolute inset-0 z-40 flex flex-col items-center justify-center bg-slate-900/80 backdrop-blur-md">
          <div class="bg-white text-slate-900 p-8 rounded-3xl shadow-2xl text-center max-w-sm w-full mx-6 animate-in zoom-in-90 duration-300">
            <div class="mb-4 flex justify-center">
              <span class="text-4xl animate-spin">🔄</span>
            </div>
            <h3 class="text-2xl font-black uppercase tracking-tight mb-2">Fim da Ronda</h3>
            <p class="text-slate-500 font-medium text-sm mb-6">A baralhar e a trocar de campo...</p>

            <div class="bg-slate-100 rounded-xl p-4 flex justify-between items-center border border-slate-200">
                <div class="text-center">
                    <p class="text-[10px] font-black uppercase text-slate-400">Tu</p>
                    <p class="text-2xl font-black text-indigo-600">{{ myMarks }}</p>
                </div>
                <div class="text-slate-300 font-black text-xs uppercase tracking-widest">Marcas</div>
                <div class="text-center">
                    <p class="text-[10px] font-black uppercase text-slate-400">Oponente</p>
                    <p class="text-2xl font-black text-rose-500">{{ opMarks }}</p>
                </div>
            </div>
          </div>
        </div>
      </Transition>

      <MatchSetup
        v-if="isMatch && (gameStatus === 'setup' || incomingProposal)"
        :current-stake="Number(game?.stake || 3)"
        :user-balance="Number(authStore.user?.coins_balance || 0)"
        :opponent-name="opponentName"
        :incoming-proposal="gameStore.incomingStakeProposal?.amount"
        :is-ready="isReady"
        :can-propose="gameStore.canIProposeStake"
        :waiting-for-opponent="isWaitingForDecision"
        @propose-stake="handleProposeStake"
        @accept-proposal="handleAnswerProposal(true)"
        @reject-proposal="handleAnswerProposal(false)"
        @ready="handleReady"
      />

      <Transition name="result">
        <div v-if="gameResult" class="fixed inset-0 bg-slate-950/90 flex items-center justify-center z-[100] backdrop-blur-xl p-6">
          <div class="w-full max-w-md bg-white rounded-[3rem] shadow-2xl overflow-hidden">

            <div class="pt-12 pb-8 px-8 text-center" :class="gameResult.isWinner ? 'bg-amber-50' : 'bg-slate-50'">
              <div class="text-8xl mb-6 scale-110 drop-shadow-2xl">
                {{ gameResult.isWinner ? '🏆' : gameResult.isDraw ? '🤝' : '💀' }}
              </div>
              <h2 class="text-4xl font-black uppercase tracking-tighter mb-2" :class="gameResult.isWinner ? 'text-amber-600' : 'text-slate-800'">
                {{ gameResult.isWinner ? 'Vitória!' : gameResult.isDraw ? 'Empate' : 'Derrota' }}
              </h2>
              <p class="text-[10px] font-black tracking-widest uppercase text-slate-400">
                {{ isMatch ? 'Match Finalizado' : 'Jogo Standalone' }}
              </p>
            </div>

            <div class="px-10 py-8 border-y border-slate-100 bg-white">
              <div class="flex justify-around items-center mb-6">
                <div class="text-center">
                  <p class="text-[9px] font-black text-slate-400 uppercase mb-2">Tu</p>
                  <p class="text-6xl font-black text-slate-900">
                    {{ isMatch ? gameResult.myMarks : gameResult.myPoints }}
                  </p>
                </div>
                <div class="text-slate-200 text-3xl font-thin">{{ isMatch ? 'MARCAS' : 'PONTOS' }}</div>
                <div class="text-center">
                  <p class="text-[9px] font-black text-slate-400 uppercase mb-2">{{ opponentName }}</p>
                  <p class="text-6xl font-black text-slate-900">
                    {{ isMatch ? gameResult.opMarks : gameResult.opPoints }}
                  </p>
                </div>
              </div>

              <div v-if="isMatch" class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                <p class="text-[8px] font-black text-slate-400 uppercase text-center mb-2 tracking-widest">Última Ronda (Pontos)</p>
                <div class="flex justify-between px-6 font-mono font-bold text-slate-600 text-sm">
                  <span>{{ gameResult.myPoints }} pts</span>
                  <span class="text-slate-300">|</span>
                  <span>{{ gameResult.opPoints }} pts</span>
                </div>
              </div>
            </div>

            <div class="p-8 bg-white flex flex-col gap-3">
              <button @click="playAgain" class="w-full py-5 rounded-2xl text-white font-black bg-indigo-600 hover:bg-indigo-700 shadow-lg active:scale-95 transition-all">
                JOGAR NOVAMENTE
              </button>

              <button @click="quitGameAndGoMenu" class="w-full py-4 rounded-2xl text-slate-500 font-bold bg-slate-100 hover:bg-slate-200 active:scale-95 transition-all">
                VOLTAR AO MENU
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter, onBeforeRouteLeave } from 'vue-router'
import { useGameStore } from '@/stores/game'
import { useAuthStore } from '@/stores/auth'
import socketService from '@/services/socket'

import GameBoard from '@/components/game/GameBoard.vue'
import MatchSetup from '@/components/game/MatchSetup.vue'

const router = useRouter()
const gameStore = useGameStore()
const authStore = useAuthStore()

const isLoading = ref(true)
const isReady = ref(false)

// --- PROPRIEDADES COMPUTADAS ---

// NF1: Utiliza a Pinia Store como fonte reativa única de dados do jogo
const game = computed(() => gameStore.activeMultiplayerGame)
const gameResult = computed(() => gameStore.gameResult)
const isMatch = computed(() => !!gameStore.isMatch)
const incomingProposal = computed(() => gameStore.incomingStakeProposal)
const isSinglePlayer = computed(() => game.value?.mode === 'single')

// NF5: Bloqueia a interface de setup se houver uma proposta externa a aguardar resposta
const isWaitingForDecision = computed(() => {
  return !!game.value?.pendingStake && !gameStore.incomingStakeProposal
})

// NF7: Resolve a identidade para comparação lógica entre utilizadores autenticados e convidados
const effectiveUserID = computed(() => {
  return authStore.user?.id || `anon-${socketService.socket?.id}`
})

/**
 * G3: Mapeia os estados técnicos do servidor para estados visuais da interface.
 * Inclui o estado de 'intermission' para transições entre rondas de uma partida.
 */
const gameStatus = computed(() => {
  if (!game.value?.status) return null
  const s = game.value.status.toLowerCase()

  if (['pending', 'pe'].includes(s)) return 'pending'
  if (s === 'setup') return 'setup'
  if (s === 'roundended') return 'intermission'
  if (['playing', 'pl'].includes(s)) return 'playing'

  return 'ended'
})

/**
 * Resolve o nome do oponente comparando o ID local com o ID do jogador1 na sala.
 */
const opponentName = computed(() => {
  if (!game.value) return 'Oponente'
  const myId = String(effectiveUserID.value)
  const opp = String(game.value.player1?.id) === myId ? game.value.player2 : game.value.player1
  return opp?.nickname || 'Oponente'
})

// Helpers visuais para o placar de marcas (Match) exibido em intermissões
const myMarks = computed(() => {
    if (!game.value) return 0
    return String(game.value.player1?.id) === String(effectiveUserID.value)
        ? game.value.player1_marks
        : game.value.player2_marks
})

const opMarks = computed(() => {
    if (!game.value) return 0
    return String(game.value.player1?.id) === String(effectiveUserID.value)
        ? game.value.player2_marks
        : game.value.player1_marks
})

// --- AÇÕES DO JOGO (WEB SOCKETS) ---

// G3: Envia a jogada de carta para validação no servidor
const handlePlayCard = (cardId) => {
  if (game.value?.id) socketService.playCard(game.value.id, cardId)
}

/**
 * G3: Permite a desistência voluntária.
 * O incumprimento (saída forçada ou desistência) atribui vitória máxima ao adversário.
 */
const handleResign = () => {
  if (window.confirm('Ao desistir, o oponente ganha a vitória máxima. Confirmas?')) {
    socketService.resignGame(game.value?.id)
  }
}

// G3: Negociação de moedas (Apostas)
const handleProposeStake = (amount) => {
  if (game.value?.id) socketService.proposeStake(game.value.id, amount)
}

const handleAnswerProposal = (accepted) => {
  if (game.value?.id) socketService.answerProposal(game.value.id, accepted)
}

const handleReady = () => {
  isReady.value = true
  socketService.emitReady(game.value?.id)
}

// --- NAVEGAÇÃO E LIMPEZA (NF7/NF9) ---

/**
 * Implementa a proteção contra saídas acidentais em jogos ativos (NF5).
 * Se o jogo estiver em curso, a saída é tratada como desistência.
 */
const handleSafeExit = (callback) => {
  if (['playing', 'setup', 'intermission'].includes(gameStatus.value) && !isSinglePlayer.value) {
    if (window.confirm('Partida em curso! Sair agora conta como derrota. Continuar?')) {
      socketService.resignGame(game.value?.id)
      gameStore.resetState()
      callback()
    }
  }
  else {
    // Se o jogo for pendente, cancela a sala no lobby
    if (gameStatus.value === 'pending') socketService.cancelGame(game.value?.id)
    gameStore.resetState()
    callback()
  }
}

const quitGameAndGoMenu = () => {
    handleSafeExit(() => {
        router.push({ name: 'home' })
    })
}

/**
 * Reinicia o ciclo de jogo.
 * Se for single-player, cria um novo jogo contra bot automaticamente.
 */
const playAgain = () => {
  if (isSinglePlayer.value) {
      const type = game.value.type
      gameStore.resetState()
      socketService.createGame({ mode: 'single', type: type, isMatch: false })
  } else {
      gameStore.resetState()
      router.push('/lobby')
  }
}

// --- CICLO DE VIDA E SINCRONIZAÇÃO ---

onMounted(() => {
  socketService.init()
  if (gameStore.activeMultiplayerGame) isLoading.value = false
})

/**
 * NF5: Interceta a navegação por rota para validar se o utilizador pode sair da mesa sem penalização.
 */
onBeforeRouteLeave((to, from, next) => {
  if (gameResult.value || !game.value) return next()
  handleSafeExit(() => next())
})

// Watchers para sincronização de estado local baseado em eventos de rede
watch(() => gameStore.activeMultiplayerGame, (val) => {
  if (val) isLoading.value = false
}, { immediate: true })

watch(() => game.value?.status, (newStatus) => {
  // NF5: Se o servidor iniciou uma nova ronda, reseta estados locais de pronto e propostas pendentes
  if (['Playing', 'PL'].includes(newStatus)) {
    isReady.value = false
    gameStore.incomingStakeProposal = null
  }
})
</script>

<style scoped>
/* NF4: Transições fluidas para feedbacks visuais de vitória e derrota (NF5) */
.result-enter-active,
.fade-enter-active,
.fade-leave-active {
  transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.result-enter-from,
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
