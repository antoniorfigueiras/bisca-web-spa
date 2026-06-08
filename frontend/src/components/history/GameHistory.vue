<template>
  <div class="flex flex-col min-h-[80vh] bg-slate-50 p-4 md:p-8">
    <Card
      class="w-full max-w-6xl mx-auto shadow-xl border-t-4 bg-white transition-all duration-500"
      :class="isAdminMode ? 'border-t-slate-900' : 'border-t-indigo-600'"
    >
      <CardHeader class="pb-2 border-b border-slate-100">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div>
            <CardTitle class="text-2xl font-bold text-slate-900 flex items-center gap-2">
              <span class="text-3xl">{{ isAdminMode ? '🛡️' : '📜' }}</span>
              {{ isAdminMode ? 'Auditoria Global' : 'O Meu Histórico' }}
            </CardTitle>
            <CardDescription class="font-medium text-slate-500">
              {{
                isAdminMode
                  ? 'Rastreio completo de todas as partidas na plataforma.'
                  : 'Consulta o teu registo de vitórias, capotes e bandeiras.'
              }}
            </CardDescription>
          </div>

          <div
            v-if="isAdminMode"
            class="bg-slate-900 text-white text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest shadow-sm flex items-center gap-2"
          >
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Modo Administrador
          </div>
        </div>
      </CardHeader>

      <CardContent class="p-0">
        <div v-if="isLoading" class="py-16 text-center text-slate-500">
          <div
            class="animate-spin h-10 w-10 border-4 border-slate-200 border-t-indigo-600 rounded-full mx-auto mb-4"
          ></div>
          <span class="font-black uppercase text-xs tracking-tighter">A carregar registos...</span>
        </div>

        <div v-else-if="games.length === 0" class="py-16 text-center text-slate-500">
          <span class="text-5xl block mb-4">📭</span>
          <p class="text-lg font-medium">Ainda não existem partidas registadas.</p>
        </div>

        <div v-else>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-500">
              <thead
                class="text-[10px] text-slate-400 uppercase bg-slate-50/50 border-b font-black tracking-widest"
              >
                <tr>
                  <th class="px-6 py-4">Data / Hora</th>
                  <th class="px-6 py-4">Tipo</th>

                  <th class="px-6 py-4">
                    {{ isAdminMode ? 'Confronto (P1 vs P2)' : 'Adversário' }}
                  </th>

                  <th class="px-6 py-4 text-center">
                    {{ isAdminMode ? 'Vencedor' : 'Resultado' }}
                  </th>
                  <th class="px-6 py-4 text-center">
                    Placar {{ isMatchHeader ? '(Marcas)' : '(Pontos)' }}
                  </th>
                  <th class="px-6 py-4 text-right">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <template v-for="item in games" :key="item.id">
                  <tr
                    class="hover:bg-slate-50/80 transition-colors group"
                    :class="{ 'bg-indigo-50/30': expandedMatches.has(item.id) }"
                  >
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="font-bold text-slate-700">
                        {{ formatDate(item.ended_at || item.began_at) }}
                      </div>
                      <div class="text-[10px] text-slate-400 font-black uppercase tracking-tighter">
                        {{ formatTime(item.ended_at || item.began_at) }}
                      </div>
                    </td>

                    <td class="px-6 py-4">
                      <div class="flex items-center gap-2">
                        <span
                          class="px-2 py-0.5 rounded text-[9px] font-black border uppercase"
                          :class="
                            isMatch(item)
                              ? 'bg-indigo-600 text-white border-indigo-700'
                              : 'bg-slate-100 text-slate-600 border-slate-200'
                          "
                        >
                          {{ isMatch(item) ? 'MATCH' : 'GAME' }}
                        </span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase"
                          >Bisca {{ item.type }}</span
                        >
                      </div>
                    </td>

                    <td class="px-6 py-4">
                      <div v-if="isAdminMode" class="flex items-center gap-2">
                        <div
                          class="flex items-center gap-2"
                          :class="{ 'opacity-50': isWinner(item, item.player2) }"
                        >
                          <div class="h-6 w-6 rounded-full overflow-hidden border bg-slate-100">
                            <img
                              :src="getAvatarUrl(item.player1)"
                              class="h-full w-full object-cover"
                            />
                          </div>
                          <span class="font-bold text-slate-700 text-xs truncate max-w-20">{{
                            item.player1?.nickname
                          }}</span>
                        </div>
                        <span class="text-slate-300 font-black text-[10px]">VS</span>
                        <div
                          class="flex items-center gap-2"
                          :class="{ 'opacity-50': isWinner(item, item.player1) }"
                        >
                          <div class="h-6 w-6 rounded-full overflow-hidden border bg-slate-100">
                            <img
                              :src="getAvatarUrl(item.player2)"
                              class="h-full w-full object-cover"
                            />
                          </div>
                          <span class="font-bold text-slate-700 text-xs truncate max-w-20">{{
                            item.player2?.nickname
                          }}</span>
                        </div>
                      </div>

                      <div v-else class="flex items-center gap-3">
                        <div
                          class="h-8 w-8 rounded-full overflow-hidden border border-slate-200 bg-slate-100"
                        >
                          <img
                            :src="getAvatarUrl(getOpponent(item))"
                            class="h-full w-full object-cover"
                          />
                        </div>
                        <span class="font-bold text-slate-700">{{ getOpponentName(item) }}</span>
                      </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                      <div v-if="isAdminMode">
                        <span
                          v-if="item.is_draw"
                          class="bg-slate-100 text-slate-500 text-[10px] font-black px-3 py-1 rounded-full border border-slate-200"
                        >
                          EMPATE
                        </span>
                        <span
                          v-else
                          class="text-emerald-600 font-bold text-xs flex items-center justify-center gap-1"
                        >
                          🏆 {{ getWinnerName(item) }}
                        </span>
                      </div>

                      <div v-else>
                        <span
                          v-if="checkIfIWon(item)"
                          class="bg-green-100 text-green-800 text-[10px] font-black px-3 py-1 rounded-full border border-green-200"
                          >🏆 VITÓRIA</span
                        >
                        <span
                          v-else-if="item.is_draw"
                          class="bg-slate-100 text-slate-600 text-[10px] font-black px-3 py-1 rounded-full border border-slate-200"
                          >EMPATE</span
                        >
                        <span
                          v-else
                          class="bg-red-100 text-red-800 text-[10px] font-black px-3 py-1 rounded-full border border-red-200"
                          >DERROTA</span
                        >
                      </div>
                    </td>

                    <td class="px-6 py-4 text-center">
                      <div class="inline-flex items-center font-mono font-black text-sm">
                        <span
                          :class="
                            isWinner(item, item.player1) ? 'text-emerald-600' : 'text-slate-400'
                          "
                        >
                          {{ isMatch(item) ? item.player1_marks : item.player1_points }}
                        </span>
                        <span class="text-slate-300 mx-2">-</span>
                        <span
                          :class="
                            isWinner(item, item.player2) ? 'text-emerald-600' : 'text-slate-400'
                          "
                        >
                          {{ isMatch(item) ? item.player2_marks : item.player2_points }}
                        </span>
                      </div>
                    </td>

                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <Button
                          v-if="isMatch(item)"
                          @click="toggleMatch(item.id)"
                          variant="ghost"
                          size="sm"
                          class="h-8 w-8 p-0 rounded-full hover:bg-indigo-100 transition-colors"
                        >
                          <span
                            class="transition-transform duration-300"
                            :class="{ 'rotate-180': expandedMatches.has(item.id) }"
                            >▼</span
                          >
                        </Button>
                        <Button
                          @click="openDetails(item)"
                          variant="outline"
                          size="sm"
                          class="h-8 text-[10px] font-black uppercase hover:bg-slate-900 hover:text-white"
                        >
                          Detalhes
                        </Button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="isMatch(item) && expandedMatches.has(item.id)">
                    <td colspan="6" class="p-0 bg-slate-50/50 border-b border-indigo-100">
                      <div
                        class="px-4 md:px-16 py-6 border-l-4 border-indigo-500 animate-in slide-in-from-top-2 duration-300"
                      >
                        <h5
                          class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4"
                        >
                          Rondas da Partida
                        </h5>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                          <div
                            v-for="(g, idx) in item.games"
                            :key="g.id"
                            class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 text-xs shadow-sm"
                          >
                            <div class="flex items-center gap-4">
                              <span
                                class="h-6 w-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black text-[10px]"
                                >#{{ idx + 1 }}</span
                              >
                              <span class="font-bold text-slate-600">{{
                                formatTime(g.ended_at)
                              }}</span>
                            </div>
                            <div class="flex items-center gap-4 font-mono font-black">
                              <span
                                :class="
                                  g.player1_points > g.player2_points
                                    ? 'text-emerald-600'
                                    : 'text-slate-400'
                                "
                                >{{ g.player1_points }}</span
                              >
                              <span class="text-slate-200 text-lg">×</span>
                              <span
                                :class="
                                  g.player2_points > g.player1_points
                                    ? 'text-emerald-600'
                                    : 'text-slate-400'
                                "
                                >{{ g.player2_points }}</span
                              >
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <div class="p-4 border-t border-slate-100 bg-slate-50/30 flex justify-center">
            <Pagination
              v-if="pagination && pagination.last_page > 1"
              :current-page="pagination.current_page || 1"
              :last-page="pagination.last_page || 1"
              :total="pagination.total || 0"
              :from="pagination.from || 0"
              :to="pagination.to || 0"
              @page-change="changePage"
            />
          </div>
        </div>
      </CardContent>
    </Card>

    <GameDetail
      v-if="selectedGame"
      :is-open="isModalOpen"
      :game="selectedGame"
      :current-user-id="Number(authStore.user?.id || 0)"
      @close="isModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAPIStore } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import Pagination from '@/components/common/Pagination.vue'
import GameDetail from '@/components/history/GameDetail.vue'

/**
 * G4/G5: Componente de Histórico.
 * Implementa a visualização de jogos multiplayer para utilizadores e auditoria total para admins.
 */
const props = defineProps({
  isAdminMode: { type: Boolean, default: false },
})

const apiStore = useAPIStore()
const authStore = useAuthStore()

// --- ESTADOS ---
const games = ref([])
const isLoading = ref(true)
const expandedMatches = ref(new Set())
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const isModalOpen = ref(false)
const selectedGame = ref(null)

// --- AÇÕES DE DADOS ---
/**
 * NF2: Consome a API RESTful seguindo as permissões de acesso.
 */
const fetchHistory = async (page = 1) => {
  isLoading.value = true
  try {
    const endpoint = props.isAdminMode ? `admin/games?page=${page}` : `games/history?page=${page}`

    const response = await apiStore.get(endpoint)
    games.value = response.data.data
    pagination.value = response.data.meta || { current_page: 1, last_page: 1 }
  } catch (e) {
    console.error('Erro ao carregar histórico:', e)
  } finally {
    isLoading.value = false
  }
}

// --- VISUALIZAÇÃO ---
const isMatch = (item) => !!(item.is_match_entry === true || (item.games && item.games.length > 0))
const isMatchHeader = computed(() => games.value.some((g) => isMatch(g)))

const toggleMatch = (id) => {
  if (expandedMatches.value.has(id)) expandedMatches.value.delete(id)
  else expandedMatches.value.add(id)
}

// --- LÓGICA DE VENCEDOR ROBUSTA ---

/**
 * G3/G4: Determina o vencedor com base nas regras de negócio.
 * Prioriza dados explícitos do servidor e utiliza lógica de fallback (Marcas > Pontos).
 */
const getWinnerId = (item) => {
  // 1. Prioridade: Servidor é a fonte da verdade.
  if (item.winner?.id) return item.winner.id
  if (item.winner_user_id) return item.winner_user_id
  if (item.winner_id) return item.winner_id

  // 2. Fallback manual baseado no estado do jogo (Ended).
  if (item.status === 'Ended') {
    if (isMatch(item)) {
      const p1Marks = Number(item.player1_marks || 0)
      const p2Marks = Number(item.player2_marks || 0)
      if (p1Marks > p2Marks) return item.player1_user_id
      if (p2Marks > p1Marks) return item.player2_user_id
    } else {
      const p1Points = Number(item.player1_points || 0)
      const p2Points = Number(item.player2_points || 0)
      if (p1Points > p2Points) return item.player1_user_id
      if (p2Points > p1Points) return item.player2_user_id
    }
  }
  return null
}

const isWinner = (game, player) => {
  if (!player || game.is_draw) return false
  const wId = getWinnerId(game)
  return wId && String(wId) === String(player.id)
}

const getWinnerName = (game) => {
  if (game.is_draw) return 'Empate'
  if (game.winner?.nickname) return game.winner.nickname
  const wId = getWinnerId(game)
  if (!wId) return 'N/A'
  if (String(wId) === String(game.player1?.id)) return game.player1?.nickname || 'Player 1'
  if (String(wId) === String(game.player2?.id)) return game.player2?.nickname || 'Player 2'
  return 'Desconhecido'
}

// --- LÓGICA JOGADOR (Client View) ---

const isMePlayer1 = (item) => Number(item.player1_user_id) === Number(authStore.user?.id)

const checkIfIWon = (item) => {
  if (item.is_draw) return false
  const wId = getWinnerId(item)

  if (wId !== null) {
    return Number(wId) === Number(authStore.user?.id)
  }

  // Último recurso: comparar scores locais se o ID falhar
  const myScore = isMatch(item)
    ? isMePlayer1(item)
      ? item.player1_marks
      : item.player2_marks
    : isMePlayer1(item)
      ? item.player1_points
      : item.player2_points

  const opScore = isMatch(item)
    ? isMePlayer1(item)
      ? item.player2_marks
      : item.player1_marks
    : isMePlayer1(item)
      ? item.player2_points
      : item.player1_points

  return Number(myScore) > Number(opScore)
}

const getOpponent = (item) => (isMePlayer1(item) ? item.player2 : item.player1)
const getOpponentName = (item) => getOpponent(item)?.nickname || 'Desconhecido'

// --- UTILS ---
const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('pt-PT', { day: '2-digit', month: 'short' }) : '-'
const formatTime = (d) =>
  d ? new Date(d).toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' }) : ''

const getAvatarUrl = (user) => {
  if (user?.photo_avatar_filename)
    return `http://localhost:8000/api/files/photos_avatars/${user.photo_avatar_filename}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.nickname || 'U')}&background=random`
}

const changePage = (p) => fetchHistory(p)
const openDetails = (g) => {
  selectedGame.value = g
  isModalOpen.value = true
}

onMounted(() => fetchHistory())
</script>
