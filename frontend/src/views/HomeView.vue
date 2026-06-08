<template>
  <div class="min-h-[85vh] flex flex-col p-4 animate-in fade-in duration-500">

    <header class="text-center py-10 md:py-16 max-w-4xl mx-auto space-y-4">
      <template v-if="!authStore.user">
        <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight">
          Bisca <span class="text-transparent bg-clip-text bg-linear-to-r from-indigo-600 to-purple-600">DAD</span>
        </h1>
        <p class="text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
          O clássico jogo de cartas português redefinido. Joga agora contra o Bot ou regista-te para desafiar a comunidade!
        </p>
      </template>

      <template v-else>
        <h1 class="text-3xl font-black text-slate-900 leading-tight">
          {{ isAdmin ? 'Consola de Administração' : `Bom vê-lo novamente, ${authStore.user.nickname || authStore.user.name}` }}! 👋
        </h1>
        <p class="text-slate-500 font-medium">
          {{ isAdmin ? 'Gira utilizadores e audite a atividade financeira da plataforma.' : 'O seu lugar na mesa está à espera.' }}
        </p>
      </template>
    </header>

    <div class="grid gap-8 md:grid-cols-2 w-full max-w-5xl mx-auto mb-16">

      <template v-if="!isAdmin">
        <Card class="w-full shadow-lg hover:shadow-2xl transition-all duration-300 border-t-4 border-t-indigo-500 bg-white group">
          <CardHeader class="space-y-1">
            <div class="flex justify-center mb-4">
              <div class="bg-indigo-50 text-indigo-600 p-4 rounded-full shadow-sm group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="10" x="3" y="11" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/></svg>
              </div>
            </div>
            <CardTitle class="text-2xl font-bold text-center">Treino contra o Bot</CardTitle>
            <CardDescription class="text-center font-medium">Joga sem moedas e sem pressão.</CardDescription>
          </CardHeader>
          <CardContent class="grid gap-6 pt-2">
            <div class="grid grid-cols-2 gap-4">
              <div v-for="variant in variants" :key="variant.value"
                @click="selectedVariant = variant.value"
                class="cursor-pointer border-2 rounded-xl p-3 flex flex-col items-center gap-2 transition-all select-none"
                :class="selectedVariant === variant.value ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-100' : 'border-slate-100 hover:border-indigo-200 hover:bg-slate-50'">
                <span class="text-2xl">{{ variant.icon }}</span>
                <span class="font-bold text-xs uppercase tracking-widest">{{ variant.label }}</span>
              </div>
            </div>
          </CardContent>
          <CardFooter>
            <Button class="w-full h-14 text-lg font-black bg-slate-900 hover:bg-black text-white shadow-xl transition-all active:scale-95" :disabled="isLoading" @click="startSinglePlayer">
              {{ isLoading ? 'A PREPARAR...' : 'JOGAR AGORA 🎮' }}
            </Button>
          </CardFooter>
        </Card>

        <Card class="w-full shadow-lg hover:shadow-2xl transition-all duration-300 border-t-4 border-t-emerald-500 bg-white group">
          <CardHeader class="space-y-1">
            <div class="flex justify-center mb-4">
              <div class="bg-emerald-50 text-emerald-600 p-4 rounded-full shadow-sm group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              </div>
            </div>
            <CardTitle class="text-2xl font-bold text-center">Partida Multijogador</CardTitle>
            <CardDescription class="text-center font-medium">Desafia humanos e domina o ranking.</CardDescription>
          </CardHeader>
          <CardContent class="flex flex-col items-center justify-center text-center space-y-4 px-8 min-h-[120px]">
            <div v-if="!authStore.user" class="space-y-4">
              <div class="bg-amber-50 text-amber-700 px-4 py-2 rounded-lg text-xs font-black border border-amber-200 inline-block uppercase tracking-tighter">🔒 Requer Autenticação</div>
              <p class="text-slate-500 text-sm font-medium">Cria conta para aceder ao Lobby e moedas.</p>
            </div>
            <div v-else class="space-y-3">
              <p class="text-emerald-700 font-bold text-sm uppercase tracking-widest animate-pulse">● Lobby Disponível</p>
              <p class="text-slate-500 text-sm">Entra na sala para encontrar oponentes reais.</p>
            </div>
          </CardContent>
          <CardFooter>
            <Button v-if="authStore.user" class="w-full h-14 text-lg font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-xl transition-all active:scale-95" @click="goToLobby">
              ENTRAR NO LOBBY 🚀
            </Button>
            <div v-else class="w-full grid grid-cols-2 gap-3">
              <Button variant="outline" class="h-12 font-bold" @click="router.push('/login')">ENTRAR</Button>
              <Button class="bg-emerald-600 hover:bg-emerald-700 text-white h-12 font-bold" @click="router.push('/register')">REGISTAR</Button>
            </div>
          </CardFooter>
        </Card>
      </template>

      <template v-else>
        <Card class="w-full shadow-lg hover:shadow-2xl transition-all duration-300 border-t-4 border-t-slate-900 bg-white group">
          <CardHeader class="space-y-1">
            <div class="flex justify-center mb-4">
              <div class="bg-slate-100 text-slate-900 p-4 rounded-full shadow-sm group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 12h6"/><path d="M12 9v6"/></svg>
              </div>
            </div>
            <CardTitle class="text-2xl font-bold text-center">Gestão de Plataforma</CardTitle>
          </CardHeader>
          <CardFooter>
            <Button class="w-full h-14 text-lg font-black bg-slate-900 hover:bg-black text-white shadow-xl transition-all active:scale-95" @click="router.push('/admin')">
              ABRIR PAINEL 🛡️
            </Button>
          </CardFooter>
        </Card>

        <Card class="w-full shadow-lg hover:shadow-2xl transition-all duration-300 border-t-4 border-t-emerald-600 bg-white group">
          <CardHeader class="space-y-1">
            <div class="flex justify-center mb-4">
              <div class="bg-emerald-50 text-emerald-600 p-4 rounded-full shadow-sm group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
              </div>
            </div>
            <CardTitle class="text-2xl font-bold text-center">Auditoria do Sistema</CardTitle>
          </CardHeader>
          <CardContent class="grid grid-cols-1 gap-3 px-8">
            <Button variant="outline" class="h-12 font-bold justify-between group/btn" @click="router.push('/admin/history/games')">
              <span>📜 Histórico de Jogos</span>
              <span class="group-hover/btn:translate-x-1 transition-transform">→</span>
            </Button>
            <Button variant="outline" class="h-12 font-bold justify-between group/btn" @click="router.push('/admin/history/wallet')">
              <span>🪙 Transações</span>
              <span class="group-hover/btn:translate-x-1 transition-transform">→</span>
            </Button>
          </CardContent>
        </Card>
      </template>
    </div>

    <section v-if="!isAdmin" class="w-full max-w-3xl mx-auto px-4 pb-20 animate-in slide-in-from-bottom-4 duration-700">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2"><span>🏆</span> Hall of Fame</h2>
        <Button variant="link" class="text-indigo-600 font-bold" @click="router.push('/leaderboard')">VER RANKING COMPLETO →</Button>
      </div>
      <LeaderboardTable :items="topPlayers" :is-loading="isLoadingStats" value-key="total_wins" label="Vitórias" />
    </section>

  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/services/api'
import { useGameStore } from '@/stores/game'
import socketService from '@/services/socket'

import LeaderboardTable from '@/components/stats/LeaderboardTable.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()
const apiStore = useAPIStore()
const gameStore = useGameStore()

// --- ESTADO ---
const isLoading = ref(false)
const isLoadingStats = ref(false)
const topPlayers = ref([])
const selectedVariant = ref('9')

/**
 * G5: Identifica utilizadores com privilégios administrativos.
 */
const isAdmin = computed(() => authStore.user?.type === 'A')

/**
 * Definição das variantes do jogo conforme o regulamento.
 */
const variants = [
  { value: '3', label: 'Bisca de 3', icon: '🃏' },
  { value: '9', label: 'Bisca de 9', icon: '🎴' },
]

// --- AÇÕES ---
const goToLobby = () => router.push('/lobby')

/**
 * G3: Inicia uma partida contra o Bot (Single-Player).
 * Utiliza WebSockets para processar a lógica do bot no servidor (NF9).
 */
const startSinglePlayer = () => {
  if (isLoading.value) return
  isLoading.value = true

  // Garante inicialização do serviço socket
  socketService.init()
  socketService.createGame({
    type: selectedVariant.value,
    mode: 'single'
  })
}

/**
 * NF5: Monitoriza a criação do jogo no servidor para redirecionar o utilizador à mesa.
 */
watch(() => gameStore.activeMultiplayerGame, (newGame) => {
  if (newGame) {
    isLoading.value = false
    router.push('/game')
  }
})

// --- CARREGAMENTO DE ESTATÍSTICAS (G4/G6) ---

/**
 * Procura os top 3 jogadores para exibição na página inicial.
 */
const fetchTopPlayers = async () => {
  if (isAdmin.value) return
  isLoadingStats.value = true
  try {
    const res = await apiStore.get('leaderboard?limit=3')
    topPlayers.value = res.data.data || res.data
  } catch (err) {
    console.error("Erro estatísticas home:", err)
  } finally {
    isLoadingStats.value = false
  }
}

onMounted(fetchTopPlayers)
</script>
