<template>
  <div class="container mx-auto p-6 max-w-7xl min-h-[80vh] flex flex-col font-sans">

    <div class="mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900 flex items-center gap-3">
          <span class="bg-slate-900 text-white p-2 rounded-lg text-2xl">🛡️</span>
          Painel de Administração
        </h1>
        <p class="text-slate-500 mt-1 uppercase text-[10px] font-bold tracking-widest">
          Controlo de Utilizadores e Auditoria do Sistema
        </p>
      </div>

      <div class="flex bg-white p-1 rounded-lg border shadow-sm">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-4 py-2 text-sm font-bold rounded-md transition-all flex items-center gap-2"
          :class="
            activeTab === tab.key
              ? 'bg-slate-100 text-slate-900 shadow-inner'
              : 'text-slate-500 hover:text-slate-700'
          "
        >
          <span>{{ tab.icon }}</span> {{ tab.label }}
        </button>
      </div>
    </div>

    <div v-if="activeTab === 'overview'" class="relative space-y-8 animate-in fade-in duration-300">
      <LoadingFeedback
        :visible="apiStore.isLoading && !stats.total_players"
        text="A carregar métricas do sistema..."
        class-name="absolute inset-0 z-10 bg-slate-50/50"
      />

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card class="border-t-4 border-t-indigo-500 shadow-sm">
          <CardContent class="p-6 flex items-center gap-4">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-full text-xl">👥</div>
            <div>
              <p class="text-[10px] font-bold text-slate-400 uppercase">Utilizadores</p>
              <h3 class="text-2xl font-black text-slate-800">{{ stats.total_players ?? 0 }}</h3>
            </div>
          </CardContent>
        </Card>

        <Card class="border-t-4 border-t-green-500 shadow-sm">
          <CardContent class="p-6 flex items-center gap-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-full text-xl">🎮</div>
            <div>
              <p class="text-[10px] font-bold text-slate-400 uppercase">Multiplayer</p>
              <h3 class="text-2xl font-black text-slate-800">
                {{ stats.total_multiplayer_games ?? 0 }}
              </h3>
            </div>
          </CardContent>
        </Card>

        <Card class="border-t-4 border-t-yellow-500 shadow-sm">
          <CardContent class="p-6 flex items-center gap-4">
            <div class="p-3 bg-yellow-50 text-yellow-600 rounded-full text-xl">💰</div>
            <div>
              <p class="text-[10px] font-bold text-slate-400 uppercase">Receita Bruta</p>
              <h3 class="text-2xl font-black text-slate-800">
                {{ stats.total_revenue_coins ?? 0 }} <span class="text-xs">💰</span>
              </h3>
            </div>
          </CardContent>
        </Card>

        <Card class="border-t-4 border-t-red-500 shadow-sm">
          <CardContent class="p-6 flex items-center gap-4">
            <div class="p-3 bg-red-50 text-red-600 rounded-full text-xl">⚔️</div>
            <div>
              <p class="text-[10px] font-bold text-slate-400 uppercase">Em Curso</p>
              <h3 class="text-2xl font-black text-slate-800">{{ stats.active_games ?? 0 }}</h3>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <StatChart
          type="line"
          title="Volume de Compras (€)"
          subtitle="Atividade financeira dos últimos 30 dias"
          :labels="chartsData.purchases.labels"
          :datasets="chartsData.purchases.datasets"
          :is-loading="isLoadingCharts"
        />

        <StatChart
          type="doughnut"
          title="Preferência de Jogo"
          subtitle="Distribuição por variante (3 vs 9)"
          :labels="chartsData.variants.labels"
          :datasets="chartsData.variants.datasets"
          :is-loading="isLoadingCharts"
        />

        <div class="lg:col-span-2">
          <StatChart
            type="bar"
            title="Novos Registos"
            subtitle="Crescimento de utilizadores nos últimos 6 meses"
            :labels="chartsData.registrations.labels"
            :datasets="chartsData.registrations.datasets"
            :is-loading="isLoadingCharts"
          />
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <Card
          class="hover:border-indigo-300 transition-colors cursor-pointer group"
          @click="$router.push('/admin/history/games')"
        >
          <CardHeader>
            <CardTitle class="text-lg group-hover:text-indigo-600 transition-colors"
              >📜 Histórico Global de Jogos</CardTitle
            >
            <CardDescription>Auditoria completa de todos os jogos da plataforma.</CardDescription>
          </CardHeader>
        </Card>

        <Card
          class="hover:border-indigo-300 transition-colors cursor-pointer group"
          @click="$router.push('/admin/history/wallet')"
        >
          <CardHeader>
            <CardTitle class="text-lg group-hover:text-indigo-600 transition-colors"
              >🪙 Auditoria Financeira</CardTitle
            >
            <CardDescription>Rastreio de todas as compras e movimentos de moedas.</CardDescription>
          </CardHeader>
        </Card>
      </div>
    </div>

    <Card
      v-else-if="activeTab === 'users'"
      class="flex-1 relative shadow-md animate-in fade-in duration-300"
    >
      <CardHeader class="border-b border-slate-100 pb-4">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <div>
            <CardTitle>Gestão de Utilizadores</CardTitle>
            <CardDescription>Visualiza, bloqueia ou promove contas.</CardDescription>
          </div>

          <div class="flex items-center gap-3 w-full md:w-auto">
            <select
              v-model="filterType"
              @change="onFilterChange"
              class="h-10 px-3 py-2 rounded-md border border-slate-200 bg-white text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
            >
              <option value="">🌍 Todos os Tipos</option>
              <option value="P">👤 Jogadores</option>
              <option value="A">🛡️ Administradores</option>
            </select>

            <div class="relative w-full md:w-64">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
              <Input
                v-model="searchQuery"
                @input="debouncedSearch"
                placeholder="Procurar nome ou nickname..."
                class="pl-10 text-sm"
              />
            </div>
          </div>
        </div>
      </CardHeader>

      <CardContent class="p-0 min-h-[400px]">
        <LoadingFeedback
          :visible="isLoadingUsers"
          text="A atualizar lista de utilizadores..."
          size="lg"
          class-name="py-20"
        />

        <div v-if="!isLoadingUsers" class="overflow-x-auto">
          <table class="w-full text-sm text-left text-slate-500">
            <thead class="text-[10px] text-slate-400 uppercase bg-slate-50 border-b font-black">
              <tr>
                <th class="px-6 py-4">Utilizador</th>
                <th class="px-6 py-4">Email / Tipo</th>
                <th class="px-6 py-4 text-center">Status</th>
                <th class="px-6 py-4 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="user in users" :key="user.id" class="bg-white hover:bg-slate-50/50">
                <td class="px-6 py-4 flex items-center gap-3">
                  <div class="h-8 w-8 rounded-full bg-slate-200 overflow-hidden border">
                    <img :src="getAvatarUrl(user)" class="h-full w-full object-cover" />
                  </div>
                  <div>
                    <div class="font-bold text-slate-800">{{ user.name }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">@{{ user.nickname }}</div>
                  </div>
                </td>

                <td class="px-6 py-4">
                  <div class="flex flex-col items-start gap-1">
                    <span class="font-mono text-xs">{{ user.email }}</span>
                    <span
                      class="text-[9px] font-black px-2 py-0.5 rounded-full border uppercase tracking-wider"
                      :class="
                        user.type === 'A'
                          ? 'bg-purple-100 text-purple-700 border-purple-200'
                          : 'bg-slate-100 text-slate-500 border-slate-200'
                      "
                    >
                      {{ user.type === 'A' ? '🛡️ Admin' : '👤 Jogador' }}
                    </span>
                  </div>
                </td>

                <td class="px-6 py-4 text-center">
                  <span
                    v-if="user.blocked"
                    class="bg-red-50 text-red-600 text-[9px] font-black px-2 py-1 rounded-sm border border-red-100"
                    >🔒 Bloqueado</span
                  >
                  <span
                    v-else
                    class="bg-green-50 text-green-700 text-[9px] font-black px-2 py-1 rounded-sm border border-green-100 uppercase"
                    >✅ Ativo</span
                  >
                </td>

                <td class="px-6 py-4 text-right">
                  <div
                    class="flex items-center justify-end gap-2"
                    v-if="user.id !== authStore.user.id"
                  >
                    <Button
                      size="sm"
                      variant="ghost"
                      class="h-8 text-[10px] font-bold border hover:bg-purple-50 hover:text-purple-700"
                      @click="toggleAdminStatus(user)"
                    >
                      {{ user.type === 'A' ? '⬇️ Despromover' : '⬆️ Promover' }}
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      class="h-8 text-[10px] font-bold"
                      @click="toggleBlockUser(user)"
                    >
                      {{ user.blocked ? 'Desbloquear' : 'Bloquear' }}
                    </Button>
                    <Button
                      size="sm"
                      variant="destructive"
                      class="h-8 text-[10px] font-bold"
                      @click="confirmDeleteUser(user)"
                    >
                      Remover
                    </Button>
                  </div>
                  <span v-else class="text-[9px] text-slate-300 font-black uppercase"
                    >Minha Conta</span
                  >
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 bg-slate-50/50 border-t" v-if="!isLoadingUsers && pagination.last_page > 1">
          <Pagination
            :current-page="pagination.current_page"
            :last-page="pagination.last_page"
            :total="pagination.total"
            :from="pagination.from"
            :to="pagination.to"
            @page-change="fetchUsers"
          />
        </div>
      </CardContent>
    </Card>

    <LoadingFeedback :visible="isProcessingAction" full-screen text="A processar alteração..." />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { useAPIStore } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

// UI Components
import LoadingFeedback from '@/components/common/LoadingSpinner.vue'
import StatChart from '@/components/admin/StatChart.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import Pagination from '@/components/common/Pagination.vue'

// Stores para comunicação e feedback (NF2/NF5)
const apiStore = useAPIStore()
const authStore = useAuthStore()
const toastStore = useToastStore()

// --- ESTADO ---
const activeTab = ref('overview')
const isLoadingUsers = ref(false)
const filterType = ref('')
const isLoadingCharts = ref(false)
const isProcessingAction = ref(false)
const searchQuery = ref('')
const users = ref([])
const stats = ref({
  total_players: 0,
  total_multiplayer_games: 0,
  active_games: 0,
  total_revenue_coins: 0,
})
const pagination = ref({ current_page: 1, last_page: 1 })

// Estrutura para os gráficos do Chart.js (G6)
const chartsData = reactive({
  purchases: { labels: [], datasets: [] },
  variants: { labels: [], datasets: [] },
  registrations: { labels: [], datasets: [] },
})

const tabs = [
  { key: 'overview', label: 'Dashboard', icon: '📊' },
  { key: 'users', label: 'Utilizadores', icon: '👥' },
]

/**
 * G1: Resolve o URL da foto ou avatar para exibir na lista administrativa.
 */
const getAvatarUrl = (user) => {
  if (user.photo_avatar_filename)
    return `http://localhost:8000/api/files/photos_avatars/${user.photo_avatar_filename}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.nickname || user.name)}&background=random`
}

// --- API ACTIONS (G5/G6) ---

/**
 * G6: Obtém o sumário de estatísticas gerais para os KPIs do dashboard.
 */
const fetchStatistics = async () => {
  try {
    const response = await apiStore.get('stats/summary')
    stats.value = response.data
  } catch (e) {
    console.error('Erro estatísticas:', e)
  }
}

/**
 * G6: Carrega dados agregados para renderização de gráficos temporais e distribuições.
 */
const fetchAdminCharts = async () => {
  isLoadingCharts.value = true
  try {
    const response = await apiStore.get('admin/stats')
    const data = response.data.charts

    // Formatação para gráfico de Linha (Compras €)
    chartsData.purchases = {
      labels: data.purchases_by_day.map((d) => d.date),
      datasets: [
        {
          label: 'Total Euros',
          data: data.purchases_by_day.map((d) => d.total_euros),
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
        },
      ],
    }

    // Formatação para gráfico Doughnut (Variantes 3 vs 9)
    chartsData.variants = {
      labels: data.games_by_variant.map((v) => `Bisca de ${v.type}`),
      datasets: [
        {
          data: data.games_by_variant.map((v) => v.count),
          backgroundColor: ['#6366f1', '#f59e0b'],
        },
      ],
    }

    // Formatação para gráfico de Barras (Crescimento de utilizadores)
    chartsData.registrations = {
      labels: data.user_registrations.map((r) => r.month),
      datasets: [
        {
          label: 'Novos Jogadores',
          data: data.user_registrations.map((r) => r.count),
          backgroundColor: '#6366f1',
        },
      ],
    }
  } catch (e) {
    console.error('Erro gráficos:', e)
  } finally {
    isLoadingCharts.value = false
  }
}

/**
 * G5: Procura a lista de utilizadores com suporte a filtros e paginação.
 */
const fetchUsers = async (page = 1) => {
  isLoadingUsers.value = true
  try {
    const response = await apiStore.get(
      `admin/users?page=${page}&search=${searchQuery.value}&type=${filterType.value}`,
    )
    const resData = response.data
    users.value = resData.data || []
    const meta = resData.meta || resData
    pagination.value = {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      total: meta.total || 0,
      from: meta.from || 0,
      to: meta.to || 0,
    }
  } finally {
    isLoadingUsers.value = false
  }
}

const onFilterChange = () => {
  fetchUsers(1)
}

/**
 * NF8: Implementação de debouncing na pesquisa para reduzir carga no servidor.
 */
let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchUsers(1), 500)
}

/**
 * G5: Bloqueia ou desbloqueia o acesso de um utilizador.
 */
const toggleBlockUser = async (user) => {
  isProcessingAction.value = true
  try {
    await apiStore.patch(`admin/users/${user.id}/block`)
    user.blocked = !user.blocked
    toastStore.showSuccess(`Conta atualizada com sucesso.`)
  } finally {
    isProcessingAction.value = false
  }
}

/**
 * G5: Elimina permanentemente um utilizador.
 * Invocado com confirmação explícita para evitar erros.
 */
const confirmDeleteUser = async (user) => {
  if (!confirm(`Remover permanentemente ${user.name}?`)) return
  isProcessingAction.value = true
  try {
    await apiStore.delete(`admin/users/${user.id}`)
    users.value = users.value.filter((u) => u.id !== user.id)
    toastStore.showSuccess('Utilizador removido do sistema.')
    fetchStatistics() // Atualiza KPIs
  } finally {
    isProcessingAction.value = false
  }
}

/**
 * G5: Altera o privilégio de conta entre Admin e Jogador.
 */
const toggleAdminStatus = async (user) => {
  const novoTipo = user.type === 'A' ? 'P' : 'A'
  const acao = novoTipo === 'A' ? 'PROMOVER' : 'DESPROMOVER'

  if (!confirm(`Tem a certeza que deseja ${acao} o utilizador ${user.name}?`)) return

  isProcessingAction.value = true
  try {
    // NF2: Utiliza PUT para atualização de perfil conforme especificação da API
    const payload = {
      name: user.name,
      email: user.email,
      nickname: user.nickname ? user.nickname : null,
      type: novoTipo,
    }
    await apiStore.put(`users/${user.id}`, payload)
    user.type = novoTipo
    toastStore.showSuccess(`Sucesso! Utilizador é agora ${novoTipo === 'A' ? 'Admin' : 'Jogador'}.`)
  } catch (error) {
    const msg = error.response?.data?.message || 'Erro de validação desconhecido.'
    alert(`Erro: ${msg}`)
  } finally {
    isProcessingAction.value = false
  }
}

// Inicia a obtenção de dados ao montar o componente
onMounted(() => {
  fetchStatistics()
  fetchAdminCharts()
  fetchUsers()
})
</script>
