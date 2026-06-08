<template>
  <div
    class="container mx-auto p-4 md:p-6 max-w-6xl min-h-[80vh] flex flex-col font-sans text-slate-900"
  >
    <div class="mb-8 text-center space-y-2 relative">
      <h1 class="text-4xl font-extrabold tracking-tight">
        <span class="text-5xl md:text-6xl">🌍</span> Lobby Online
      </h1>
      <div class="flex items-center justify-center gap-3 text-sm font-medium">
        <p class="text-slate-500">Cria uma sala ou junta-te a uma partida ativa.</p>
        <span class="text-slate-300">|</span>
        <div class="flex items-center gap-2">
          <span
            class="flex h-2.5 w-2.5 rounded-full"
            :class="isConnected ? 'bg-green-500' : 'bg-red-500'"
          ></span>
          <span
            class="text-[10px] font-black uppercase tracking-widest"
            :class="isConnected ? 'text-green-600' : 'text-red-500'"
          >
            {{ isConnected ? 'Conectado' : 'A ligar...' }}
          </span>
        </div>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 md:gap-8 flex-1">

      <div class="lg:col-span-1 space-y-6">

        <div
          v-if="myActiveGame && !gameResult"
          class="bg-indigo-50 border-2 border-indigo-200 rounded-2xl p-6 shadow-xl animate-in fade-in zoom-in duration-300"
        >
          <div class="text-center mb-6">
            <div class="text-4xl mb-2">⏳</div>
            <h3 class="font-black text-indigo-900 uppercase tracking-tight">Sala Ativa</h3>
            <p class="text-xs text-indigo-600 font-medium">
              {{ isGameInProgress ? 'Partida em curso!' : 'A aguardar adversário...' }}
            </p>
          </div>

          <button
            v-if="isGameInProgress"
            @click="enterGame"
            class="w-full py-4 bg-green-600 hover:bg-green-700 text-white font-black rounded-xl shadow-lg transition-all active:scale-95"
          >
            ENTRAR NO JOGO ➡️
          </button>

          <button
            v-else
            @click="cancelMyGame"
            class="w-full py-4 bg-white hover:bg-red-50 text-red-600 border-2 border-red-100 font-black rounded-xl transition-all active:scale-95"
          >
            CANCELAR SALA 🗑️
          </button>
        </div>

        <div v-else class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
          <div class="p-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-lg flex items-center gap-2 text-slate-800">
              <span>✨</span> Criar Nova Sala
            </h3>
          </div>

          <div class="p-5 space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase text-slate-400">Variante</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="t in ['3', '9']"
                  :key="t"
                  @click="createForm.type = t"
                  :class="createForm.type === t ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-100 text-slate-500'"
                  class="p-3 border-2 rounded-xl font-bold transition-all"
                >
                  Bisca de {{ t }}
                </button>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase text-slate-400">Modo de Partida</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="createForm.isMatch = false"
                  :class="!createForm.isMatch ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-100 text-slate-500'"
                  class="p-3 border-2 rounded-xl text-xs font-bold transition-all"
                >
                  Jogo Único (2 💰)
                </button>
                <button
                  @click="createForm.isMatch = true"
                  :class="createForm.isMatch ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-slate-100 text-slate-500'"
                  class="p-3 border-2 rounded-xl text-xs font-bold transition-all"
                >
                  Match
                </button>
              </div>
            </div>

            <div v-if="createForm.isMatch" class="space-y-2 animate-in slide-in-from-top-2">
              <label class="text-[10px] font-black uppercase text-slate-400">Aposta Inicial: {{ createForm.stake }} Moedas</label>
              <input
                type="range"
                v-model.number="createForm.stake"
                min="3"
                max="100"
                class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-indigo-600"
              />
            </div>

            <button
              @click="handleCreate"
              :disabled="!canAfford || !isConnected || isAdmin"
              class="w-full py-4 bg-slate-900 text-white font-black rounded-xl hover:bg-indigo-600 transition-all disabled:opacity-50 shadow-xl shadow-slate-200"
            >
              <span v-if="isAdmin">ADMINS NÃO JOGAM</span>
              <span v-else-if="!isConnected">A LIGAR...</span>
              <span v-else>CRIAR SALA ({{ gameCost }} 💰)</span>
            </button>

            <p v-if="!canAfford && !isAdmin" class="text-[10px] text-red-500 text-center font-bold animate-pulse">
              Saldo insuficiente! (Tens {{ authStore.user?.coins_balance ?? 0 }} moedas)
            </p>
          </div>
        </div>
      </div>

      <div class="lg:col-span-2 flex flex-col h-[600px] bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden">
        <div class="p-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-4 z-10 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="bg-indigo-100 p-2 rounded-lg text-indigo-700">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" /><path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1Z" /></svg>
            </div>
            <div>
              <h2 class="font-black text-slate-800 text-sm uppercase tracking-tight">Salas Públicas</h2>
              <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">{{ filteredGames.length }} Disponíveis</span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <div class="flex bg-slate-100 p-1 rounded-lg">
              <button
                v-for="f in ['Todos', '3', '9']"
                :key="f"
                @click="activeFilter = f"
                :class="activeFilter === f ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-1.5 text-[10px] font-black uppercase rounded-md transition-all"
              >
                {{ f === 'Todos' ? 'Tudo' : `Bisca ${f}` }}
              </button>
            </div>
            <button @click="refreshList" class="p-2 hover:bg-slate-100 rounded-lg transition-colors text-slate-400 hover:text-indigo-600">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8" /><path d="M21 3v5h-5" /></svg>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 bg-slate-50/50">
          <div v-if="filteredGames.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400/60 py-20">
            <div class="text-6xl mb-4 grayscale opacity-30">🏟️</div>
            <p class="font-bold text-lg text-slate-500">Sem salas abertas de momento.</p>
          </div>

          <div v-else class="grid gap-3">
            <div
              v-for="room in filteredGames"
              :key="room.id"
              class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between shadow-sm hover:border-indigo-300 transition-all group"
            >
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center border-2 bg-indigo-50 border-indigo-100 text-indigo-600">
                  <span class="text-lg font-black leading-none">{{ room.type }}</span>
                  <span class="text-[8px] font-bold uppercase mt-0.5">Cartas</span>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="font-black text-slate-800 text-sm">
                      {{ room.isMatch ? '🏆 Partida (Match)' : '🃏 Jogo Standalone' }}
                    </h4>
                    <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-mono">#{{ room.id }}</span>
                  </div>
                  <div class="text-[10px] flex items-center gap-2 mt-1">
                    <span class="font-black uppercase text-slate-400">Criador:</span>
                    <span class="font-black text-slate-700">{{ getCreatorName(room) }}</span>
                    <span class="flex items-center gap-1 bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-bold border border-amber-100">💰 {{ room.stake }}</span>
                  </div>
                </div>
              </div>
              <button
                @click="joinGame(room.id)"
                :disabled="isAdmin || !isConnected"
                class="px-6 py-2.5 bg-slate-900 hover:bg-indigo-600 text-white text-[10px] font-black uppercase rounded-xl transition-all active:scale-95 shadow-lg disabled:opacity-50"
              >
                ACEITAR ⚔️
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import socketService from '@/services/socket'
import { useGameStore } from '@/stores/game'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const gameStore = useGameStore()
const authStore = useAuthStore()

// --- ESTADOS ---
const activeFilter = ref('Todos')
const isConnected = ref(false)
const createForm = ref({
  type: '3',
  isMatch: false,
  stake: 3,
})

// --- COMPUTED ---

/**
 * G3: Calcula o custo de entrada.
 * Jogos Standalone custam sempre 2 moedas, Matches variam conforme a aposta.
 */
const gameCost = computed(() => {
  return createForm.value.isMatch ? Number(createForm.value.stake) : 2
})

const isAdmin = computed(() => authStore.user?.type === 'A')

/**
 * G2: Verifica se o utilizador possui saldo suficiente para a operação financeira.
 */
const canAfford = computed(() => {
  const userCoins = Number(authStore.user?.coins_balance || 0)
  const cost = Number(gameCost.value)
  return userCoins >= cost
})

const myActiveGame = computed(() => gameStore.activeMultiplayerGame)
const gameResult = computed(() => gameStore.gameResult)
const myUserId = computed(() => authStore.user?.id)

/**
 * G3: Identifica se a sala está pronta para a transição para o ecrã de jogo.
 */
const isGameInProgress = computed(() => {
  const status = myActiveGame.value?.status?.toUpperCase()
  return ['PLAYING', 'PL', 'SETUP'].includes(status)
})

/**
 * G3: Filtra as salas disponíveis no servidor Socket.io.
 * Exclui jogos single-player e salas criadas pelo próprio utilizador.
 */
const filteredGames = computed(() => {
  const games = gameStore.multiplayerGames || []
  const myId = String(myUserId.value)

  return games.filter((g) => {
    if (g.mode === 'single') return false
    const status = g.status ? g.status.toUpperCase() : ''
    const isPending = ['PENDING', 'PE'].includes(status)
    const creatorId = String(g.player1_user_id || g.player1?.id || 0)
    const isNotMine = creatorId !== myId
    const matchesFilter = activeFilter.value === 'Todos' || String(g.type) === activeFilter.value
    return isPending && isNotMine && matchesFilter
  })
})

// --- MÉTODOS ---

const refreshList = () => {
  socketService.emitGetGames()
  isConnected.value = socketService.socket?.connected || false
}

/**
 * G3: Solicita ao servidor a criação de uma sala multiplayer.
 */
const handleCreate = () => {
  if (!canAfford.value || myActiveGame.value || isAdmin.value) return
  gameStore.gameResult = null
  socketService.createGame({
    type: createForm.value.type,
    isMatch: createForm.value.isMatch,
    stake: gameCost.value,
  })
}

/**
 * G3: Envia o pedido para ingressar numa sala pública.
 */
const joinGame = (gameId) => {
  if (myActiveGame.value) return
  socketService.joinGame(gameId)
}

/**
 * G3: Cancela uma sala pendente antes de outro jogador entrar.
 */
const cancelMyGame = () => {
  if (myActiveGame.value?.id) {
    socketService.cancelGame(myActiveGame.value.id)
    gameStore.resetState()
  }
}

const enterGame = () => router.push('/game')
const getCreatorName = (game) => game.player1?.nickname || game.player1?.name || 'Jogador'

// --- CICLO DE VIDA (NF9) ---

onMounted(() => {
  socketService.init()
  isConnected.value = socketService.socket?.connected || false
  socketService.socket?.on('connect', () => {
    isConnected.value = true
    refreshList()
  })
  socketService.socket?.on('disconnect', () => {
    isConnected.value = false
  })
})

/**
 * G3: Watcher de Redirecionamento.
 * Garante que a transição para a GameView só ocorre quando ambos os jogadores estão sincronizados.
 */
watch(
  () => myActiveGame.value,
  (newGame) => {
    if (!newGame) return

    const status = newGame.status?.toUpperCase()
    // Verifica a presença física do adversário no objeto de sala
    const hasOpponent = !!(newGame.player2_user_id || newGame.player2)

    if (['PLAYING', 'PL', 'SETUP'].includes(status) && hasOpponent) {
      router.push('/game')
    }
  },
  { deep: true },
)
</script>
