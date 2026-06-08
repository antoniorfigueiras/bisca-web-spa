<template>
  <div class="container mx-auto p-4 md:p-6 max-w-5xl min-h-[80vh] flex flex-col font-sans">

    <div class="text-center mb-10 space-y-2 animate-in fade-in slide-in-from-top-4 duration-500">
      <h1 class="text-4xl font-extrabold text-slate-900 flex items-center justify-center gap-3">
        <span class="text-5xl filter drop-shadow-md">🏆</span> Hall of Fame
      </h1>
      <p class="text-slate-500 text-lg font-medium">
        Os mestres da Bisca ordenados pela sua glória e perícia.
      </p>
    </div>

    <div class="flex justify-center mb-6 gap-2">
      <button
        v-for="variant in ['all', '3', '9']"
        :key="variant"
        @click="currentVariant = variant"
        class="px-4 py-2 text-xs font-bold rounded-full border transition-all"
        :class="
          currentVariant === variant
            ? 'bg-slate-900 text-white shadow-md'
            : 'bg-white text-slate-600 hover:bg-slate-50'
        "
      >
        {{ variant === 'all' ? 'Todas as Variantes' : `Bisca de ${variant}` }}
      </button>
    </div>

    <Card
      class="shadow-xl border-t-4 border-t-indigo-600 bg-white flex-1 flex flex-col overflow-hidden"
    >
      <CardHeader class="border-b border-slate-100 bg-slate-50/50">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <div>
            <CardTitle class="text-2xl font-bold text-slate-800 tracking-tight">
              {{ activeTabConfig.label }}
            </CardTitle>
            <CardDescription class="font-bold text-[10px] uppercase text-slate-400 tracking-widest">
              Atualizado em tempo real (NF5)
            </CardDescription>
          </div>

          <div
            class="flex p-1 bg-slate-100 rounded-lg shadow-inner border border-slate-200 overflow-x-auto"
          >
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="currentTab = tab.key"
              class="px-4 py-2 text-xs font-black rounded-md transition-all duration-300 uppercase whitespace-nowrap"
              :class="
                currentTab === tab.key
                  ? 'bg-white text-indigo-600 shadow-sm scale-105'
                  : 'text-slate-500 hover:text-slate-700'
              "
            >
              {{ tab.tabLabel }}
            </button>
          </div>
        </div>
      </CardHeader>

      <CardContent class="p-0 flex-1 relative min-h-[400px]">
        <LeaderboardTable
          :items="leaderboardData"
          :is-loading="isLoading"
          :label="activeTabConfig.columnLabel"
          :value-key="activeTabConfig.valueKey"
          :type="activeTabConfig.format"
        />
      </CardContent>

      <CardFooter class="bg-slate-50 border-t border-slate-100 p-4 justify-between items-center">
        <p class="text-[10px] text-slate-400 font-bold uppercase italic">
          * Em caso de empate, prevalece o registo mais antigo.
        </p>
        <span class="text-[10px] font-black text-indigo-400 bg-indigo-50 px-2 py-1 rounded">
          Total de Jogadores: {{ totalPlayersCount }}
        </span>
      </CardFooter>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAPIStore } from '@/services/api'
import LeaderboardTable from '@/components/stats/LeaderboardTable.vue'
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from '@/components/ui/card'

/**
 * G4/G6: Vista de Leaderboard Global.
 * Permite a qualquer utilizador (mesmo anónimo) consultar o mérito da comunidade.
 */
const apiStore = useAPIStore()

// --- ESTADO ---
const isLoading = ref(true)
const leaderboardData = ref([])
const currentTab = ref('wins')
const currentVariant = ref('all')
const totalPlayersCount = ref(0)

/**
 * G4: Configuração das métricas de mérito da Bisca.
 * - Vitórias: Jogos ganhos.
 * - Capotes: Jogos ganhos com pontuação entre 91 e 119.
 * - Bandeiras: Jogos ganhos com a pontuação máxima de 120.
 */
const tabs = [
  {
    key: 'wins',
    tabLabel: 'Vitórias',
    label: 'Ranking de Vitórias',
    columnLabel: 'Jogos Ganhos',
    valueKey: 'total_wins',
    format: 'number',
  },
  {
    key: 'capotes',
    tabLabel: 'Capotes',
    label: 'Mestres do Capote (91-119 pts)',
    columnLabel: 'Total Capotes',
    valueKey: 'total_capotes',
    format: 'number',
  },
  {
    key: 'bandeiras',
    tabLabel: 'Bandeiras',
    label: 'Reis da Bandeira (120 pts)',
    columnLabel: 'Total Bandeiras',
    valueKey: 'total_bandeiras',
    format: 'number',
  },
  {
    key: 'coins',
    tabLabel: 'Fortuna',
    label: 'Top Milionários',
    columnLabel: 'Saldo de Moedas',
    valueKey: 'coins_balance',
    format: 'coins',
  },
]

const activeTabConfig = computed(() => {
  return tabs.find((t) => t.key === currentTab.value) || tabs[0]
})

// --- LÓGICA DE DADOS ---

/**
 * NF2: Procura os dados na API RESTful com base nos filtros selecionados.
 * Inclui a contagem global de utilizadores para contexto estatístico (G6).
 */
const fetchLeaderboard = async () => {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      type: currentTab.value,
      variant: currentVariant.value,
    })

    const response = await apiStore.get(`leaderboard?${params.toString()}`)
    leaderboardData.value = response.data.data

    // G6: Procura estatísticas globais sumariadas
    const stats = await apiStore.get('stats/summary')
    totalPlayersCount.value = stats.data.total_players
  } catch (error) {
    console.error('Erro ao carregar dados:', error)
  } finally {
    isLoading.value = false
  }
}

// NF5: Reage instantaneamente a mudanças de filtros de variante ou categoria.
watch([currentTab, currentVariant], () => {
  fetchLeaderboard()
})

onMounted(fetchLeaderboard)
</script>
