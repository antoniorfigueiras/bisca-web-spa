<template>
  <div class="flex flex-col min-h-[80vh] bg-slate-50 p-4 md:p-8">
    <Card class="w-full max-w-5xl mx-auto shadow-lg border-t-4 bg-white transition-all duration-500"
      :class="isAdminMode ? 'border-t-slate-900' : 'border-t-yellow-500'">

      <CardHeader>
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <div>
            <CardTitle class="text-2xl font-bold text-slate-900 flex items-center gap-2">
              <span>{{ isAdminMode ? '🛡️' : '📒' }}</span>
              {{ isAdminMode ? 'Auditoria Financeira Global' : 'Histórico de Movimentos' }}
            </CardTitle>
            <CardDescription>
              {{ isAdminMode ? 'Rastreio de todas as transações de moedas na plataforma.' : 'Consulta o extrato detalhado de todas as tuas moedas.' }}
            </CardDescription>
          </div>

          <div v-if="!isAdminMode" class="bg-yellow-50 px-5 py-3 rounded-xl border border-yellow-200 shadow-sm flex flex-col items-center md:items-end">
            <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-widest">Saldo Disponível</span>
            <div class="flex items-center gap-2">
              <span class="text-3xl font-black text-yellow-600 font-mono">{{ authStore.user?.coins_balance ?? 0 }}</span>
              <span class="text-2xl">💰</span>
            </div>
          </div>

          <div v-else class="bg-slate-900 text-white text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest">
            Modo Auditoria
          </div>
        </div>
      </CardHeader>

      <CardContent class="p-0 relative min-h-[400px]">
        <LoadingFeedback
          :visible="isLoading"
          :text="isAdminMode ? 'A carregar registos financeiros...' : 'A atualizar o teu extrato...'"
          size="lg"
          class-name="py-20"
        />

        <div v-if="!isLoading && (!transactions || transactions.length === 0)" class="py-16 text-center text-slate-400">
           <span class="text-5xl block mb-4">🪙</span>
           <p>Nenhum movimento registado.</p>
        </div>

        <div v-else-if="!isLoading">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-500">
              <thead class="text-[10px] text-slate-400 uppercase bg-slate-50/50 border-b font-black tracking-widest">
                <tr>
                  <th scope="col" class="px-6 py-4">Data e Hora</th>
                  <th v-if="isAdminMode" scope="col" class="px-6 py-4">Utilizador</th>
                  <th scope="col" class="px-6 py-4">Detalhes do Movimento</th>
                  <th scope="col" class="px-6 py-4 text-right">Valor</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="t in transactions" :key="t.id" class="bg-white hover:bg-slate-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-bold text-slate-900">
                      {{ t.datetime ? new Date(t.datetime).toLocaleDateString('pt-PT') : '---' }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-black uppercase tracking-tighter">
                      {{ t.datetime ? new Date(t.datetime).toLocaleTimeString('pt-PT', { hour: '2-digit', minute:'2-digit' }) : '' }}
                    </div>
                  </td>

                  <td v-if="isAdminMode" class="px-6 py-4">
                    <div class="flex flex-col">
                      <span class="font-bold text-slate-700">{{ t.user?.nickname || t.user?.name || 'Sistema' }}</span>
                      <span class="text-[9px] text-slate-400 font-mono">{{ t.user?.email }}</span>
                    </div>
                  </td>

                  <td class="px-6 py-4">
                    <div class="flex flex-col gap-1">
                      <div class="flex items-center gap-2 flex-wrap">
                        <span
                          class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black border uppercase tracking-tighter"
                          :class="getTypeStyle(t)"
                        >
                          {{ formatTypeName(t) }}
                        </span>

                        <span v-if="t.purchase_details" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold border border-slate-200">
                          <span v-if="t.purchase_details.payment_type === 'VISA'">💳</span>
                          <span v-else-if="t.purchase_details.payment_type === 'MBWAY'">📱</span>
                          <span v-else-if="t.purchase_details.payment_type === 'PAYPAL'">🅿️</span>
                          {{ t.purchase_details.payment_type }}
                        </span>
                      </div>

                      <div class="mt-1">
                        <p v-if="t.purchase_details" class="text-[10px] text-slate-400 font-mono italic">
                          Ref: {{ t.purchase_details.payment_reference }}
                        </p>
                        <div v-if="t.game_id || t.match_id" class="flex gap-2">
                          <span v-if="t.game_id" class="text-[10px] text-indigo-500 font-black uppercase tracking-tighter">Jogo #{{ t.game_id }}</span>
                          <span v-if="t.match_id" class="text-[10px] text-purple-500 font-black uppercase tracking-tighter">Partida #{{ t.match_id }}</span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td class="px-6 py-4 text-right">
                    <span
                      class="font-mono font-black text-lg"
                      :class="t.coins > 0 ? 'text-green-600' : 'text-red-600'"
                    >
                      {{ t.coins > 0 ? '+' : '' }}{{ t.coins }} 💰
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            <Pagination
              :current-page="pagination.current_page"
              :last-page="pagination.last_page"
              :total="pagination.total"
              :from="pagination.from"
              :to="pagination.to"
              @page-change="fetchTransactions"
            />
          </div>
        </div>
      </CardContent>

      <CardFooter class="pt-6 border-t bg-slate-50 rounded-b-lg">
        <Button variant="outline" @click="isAdminMode ? $router.push('/admin') : $router.push('/profile')" class="flex gap-2 text-slate-600 font-bold text-xs">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
          {{ isAdminMode ? 'VOLTAR AO DASHBOARD' : 'VOLTAR AO PERFIL' }}
        </Button>
      </CardFooter>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAPIStore } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import LoadingFeedback from '@/components/common/LoadingSpinner.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import Pagination from '@/components/common/Pagination.vue'

/**
 * G2: Componente de Histórico de Transações.
 * Implementa a visualização de movimentos financeiros conforme as regras de negócio.
 */
const props = defineProps({
  isAdminMode: { type: Boolean, default: false }
})

const apiStore = useAPIStore()
const authStore = useAuthStore()

// --- ESTADOS ---
const transactions = ref([])
const isLoading = ref(true)
const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })

/**
 * NF2: Procura os registos na API RESTful.
 * Distingue entre o histórico pessoal do jogador e a auditoria administrativa global.
 */
const fetchTransactions = async (page = 1) => {
  isLoading.value = true
  try {
    const endpoint = props.isAdminMode
      ? `admin/transactions?page=${page}&limit=10`
      : `my/transactions?page=${page}&limit=10`

    const response = await apiStore.get(endpoint)
    const resData = response.data

    transactions.value = resData.data || []
    const meta = resData.meta || resData

    pagination.value = {
      current_page: meta.current_page || 1,
      last_page: meta.last_page || 1,
      total: meta.total || 0,
      from: meta.from || 0,
      to: meta.to || 0
    }
  } catch (error) {
    console.error('Erro ao buscar histórico:', error)
  } finally {
    isLoading.value = false
  }
}

/**
 * G2: Mapeamento de nomes técnicos para termos legíveis conforme o modelo de negócio.
 */
const formatTypeName = (t) => {
  if (!t) return ''
  // Identifica compras externas de moedas.
  if (t.purchase_details) return 'Compra de Moedas'
  const typeData = t.type
  const name = (typeof typeData === 'object' ? typeData?.name : typeData)?.trim().toLowerCase()
  const map = {
    'bonus': 'Bónus de Boas-Vindas',
    'coin purchase': 'Compra de Moedas',
    'purchase': 'Compra de Moedas',
    'game fee': 'Inscrição em Jogo',
    'match stake': 'Aposta de Partida',
    'game payout': 'Prémio Ganhos',
    'match payout': 'Vitória em Partida'
  }
  return map[name] || (name ? name.charAt(0).toUpperCase() + name.slice(1) : 'Movimento')
}

/**
 * NF4: Estilização cromática baseada no tipo de movimento para facilitar a auditoria visual.
 */
const getTypeStyle = (t) => {
  if (!t) return ''
  const name = (typeof t.type === 'object' ? t.type?.name : t.type)?.trim().toLowerCase()
  if (name === 'bonus') return 'bg-purple-100 text-purple-700 border-purple-200'
  if (name?.includes('purchase') || t.purchase_details) return 'bg-blue-100 text-blue-700 border-blue-200'
  if (name?.includes('payout')) return 'bg-green-100 text-green-700 border-green-200'
  return 'bg-orange-100 text-orange-700 border-orange-200'
}

onMounted(() => fetchTransactions())
</script>
