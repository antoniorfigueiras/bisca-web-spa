<template>
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
      @click.self="close"
    >
      <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

        <div class="bg-slate-50 border-b border-slate-100 p-6 flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
              <span class="text-2xl">{{ isMatch ? '⚔️' : '📋' }}</span>
              Detalhes {{ isMatch ? 'da Partida' : 'do Jogo' }} #{{ game.id }}
            </h3>
            <p class="text-sm text-slate-500 mt-1 uppercase text-[10px] font-bold tracking-tighter">
              Finalizado em {{ formatDate(game.ended_at) }}
            </p>
          </div>
          <button @click="close" class="p-2 rounded-full hover:bg-slate-200 text-slate-400 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

        <div class="p-6 overflow-y-auto">

          <div
            class="flex items-center justify-center p-4 rounded-lg mb-8 border-2 border-dashed"
            :class="statusClasses"
          >
            <span class="text-2xl font-black uppercase tracking-widest text-center">
              {{ resultText }}
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center mb-8">

            <div class="flex flex-col items-center text-center">
              <div class="relative mb-3">
                <img
                  :src="getAvatarUrl(game.player1)"
                  class="w-20 h-20 rounded-full object-cover border-4 transition-all"
                  :class="winnerBorder(1)"
                />
                <span v-if="isWinner(1)" class="absolute -top-3 -right-2 text-3xl drop-shadow-md">👑</span>
              </div>
              <span class="font-bold text-slate-800">{{ game.player1?.nickname || game.player1?.name || '---' }}</span>
              <span v-if="isMe(game.player1)" class="text-[10px] font-black bg-indigo-100 px-2 py-0.5 rounded text-indigo-600 mt-1 uppercase">Tu</span>
              <span v-else class="text-[9px] text-slate-400 uppercase font-bold mt-1">Jogador 1</span>
            </div>

            <div class="flex flex-col items-center gap-2">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                 {{ isMatch ? 'Marcas (Sets)' : 'Pontos' }}
              </span>
              <div class="flex items-center gap-4 text-4xl font-mono font-black text-slate-800">
                <span :class="{ 'text-emerald-600': isWinner(1) }">
                   {{ isMatch ? game.player1_marks : game.player1_points }}
                </span>
                <span class="text-slate-300 text-xl">{{ isMatch ? '-' : 'x' }}</span>
                <span :class="{ 'text-emerald-600': isWinner(2) }">
                   {{ isMatch ? game.player2_marks : game.player2_points }}
                </span>
              </div>

              <div v-if="game.match_id" class="flex flex-col items-center gap-1 mt-2">
                <div class="bg-indigo-50 px-3 py-1 rounded-full text-[10px] font-black text-indigo-700 uppercase tracking-tighter">
                  Match #{{ game.match_id }}
                </div>
              </div>
            </div>

            <div class="flex flex-col items-center text-center">
              <div class="relative mb-3">
                <img
                  :src="getAvatarUrl(game.player2)"
                  class="w-20 h-20 rounded-full object-cover border-4 transition-all"
                  :class="winnerBorder(2)"
                />
                <span v-if="isWinner(2)" class="absolute -top-3 -right-2 text-3xl drop-shadow-md">👑</span>
              </div>
              <span class="font-bold text-slate-800">{{ game.player2?.nickname || game.player2?.name || '---' }}</span>
              <span v-if="isMe(game.player2)" class="text-[10px] font-black bg-indigo-100 px-2 py-0.5 rounded text-indigo-600 mt-1 uppercase">Tu</span>
              <span v-else class="text-[9px] text-slate-400 uppercase font-bold mt-1">Jogador 2</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 text-sm bg-slate-50 p-4 rounded-lg border border-slate-100">
            <div>
              <span class="block text-slate-400 text-[10px] uppercase font-black mb-1">Variante</span>
              <span class="font-bold text-slate-700">Bisca de {{ game.type }} cartas</span>
            </div>
            <div>
              <span class="block text-slate-400 text-[10px] uppercase font-black mb-1">Duração</span>
              <span class="font-bold text-slate-700">{{ formatDuration(game.total_time) }}</span>
            </div>
            <div>
              <span class="block text-slate-400 text-[10px] uppercase font-black mb-1">Resultado Técnico</span>
              <span class="font-bold text-slate-700">{{ getVictoryType() }}</span>
            </div>
            <div>
              <span class="block text-slate-400 text-[10px] uppercase font-black mb-1">Vencedor</span>
              <span class="font-bold text-emerald-600 uppercase">
                {{ game.is_draw ? 'Ninguém (Empate)' : getWinnerName() }}
              </span>
            </div>
          </div>

          <div v-if="isMatch && game.games && game.games.length > 0" class="mt-6">
            <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">Histórico de Rondas</h4>
            <div class="space-y-2">
                <div v-for="(g, idx) in game.games" :key="g.id" class="flex justify-between items-center bg-slate-50 p-3 rounded border border-slate-100 text-xs">
                    <span class="font-bold text-slate-500">Ronda #{{ idx + 1 }}</span>
                    <div class="font-mono font-bold text-slate-700">
                        {{ g.player1_points }} - {{ g.player2_points }}
                    </div>
                </div>
            </div>
          </div>

        </div>

        <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-end">
          <button @click="close" class="px-6 py-2 bg-slate-900 text-white font-black rounded-lg hover:bg-slate-800 transition-colors text-xs uppercase tracking-widest">
            Fechar Auditoria
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Propriedades do componente:
 * isOpen: Controla a exibição.
 * game: Objeto de dados persistidos no backend.
 * currentUserId: ID do utilizador logado para identificar o vencedor/derrotado (G3).
 */
const props = defineProps({
  isOpen: Boolean,
  game: { type: Object, required: true },
  currentUserId: { type: Number, required: true }
})

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.user?.type === 'A')

const emit = defineEmits(['close'])
const close = () => emit('close')

// --- LÓGICA DE DADOS ---

// Verifica se o registo é uma partida completa (Match) ou jogo único
const isMatch = computed(() => !!(props.game.match_id || props.game.is_match_entry))

// Indica se o jogador no modal é o utilizador atual (Exceto para Admins)
const isMe = (player) => !isAdmin.value && Number(player?.id) === Number(props.currentUserId)

/**
 * G3/G4: Resolve o ID do vencedor com base nos dados do servidor ou cálculo de fallback.
 * O servidor é a fonte da verdade para o resultado.
 */
const getWinnerId = () => {
    if (props.game.winner?.id) return props.game.winner.id;
    if (props.game.winner_user_id) return props.game.winner_user_id;
    if (props.game.winner_id) return props.game.winner_id;

    // Fallback manual baseado nas marcas (Match) ou pontos (Jogo)
    if (isMatch.value) {
        if (props.game.player1_marks > props.game.player2_marks) return props.game.player1_user_id;
        if (props.game.player2_marks > props.game.player1_marks) return props.game.player2_user_id;
    } else {
        if (props.game.player1_points > props.game.player2_points) return props.game.player1_user_id;
        if (props.game.player2_points > props.game.player1_points) return props.game.player2_user_id;
    }
    return null;
}

const isWinner = (playerNum) => {
    if (props.game.is_draw) return false;
    const wId = getWinnerId();
    if (!wId) return false;

    const pId = playerNum === 1 ? props.game.player1_user_id : props.game.player2_user_id;
    return String(wId) === String(pId);
}

const checkIfIWon = () => {
  if (isAdmin.value) return false
  if (props.game.is_draw) return false
  const wId = getWinnerId();
  return wId && String(wId) === String(props.currentUserId);
}

const getWinnerName = () => {
    const wId = getWinnerId();
    if (!wId) return '---';
    if (String(wId) === String(props.game.player1_user_id)) return props.game.player1?.nickname || 'Player 1';
    if (String(wId) === String(props.game.player2_user_id)) return props.game.player2?.nickname || 'Player 2';
    return 'Desconhecido';
}

// --- VISUALIZAÇÃO ---

// Define as cores de feedback: Verde para vitória, Vermelho para derrota, Slate para empate (NF4)
const statusClasses = computed(() => {
  if (props.game.is_draw) return 'bg-slate-50 border-slate-200 text-slate-400'
  if (isAdmin.value) return 'bg-emerald-50 border-emerald-200 text-emerald-700'
  return checkIfIWon() ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'
})

const resultText = computed(() => {
  if (props.game.is_draw) return 'Empate'
  if (isAdmin.value) return `Vencedor: ${getWinnerName()}`
  return checkIfIWon() ? 'Vitória!' : 'Derrota'
})

/**
 * G3: Classifica o tipo de vitória conforme o modelo de negócio.
 * - Bandeira: 120 pontos.
 * - Capote: > 90 pontos.
 * - Forfeit/Timeout: Abandono da partida.
 */
const getVictoryType = () => {
  if (props.game.is_draw) return 'Empate'
  if (props.game.end_reason === 'Resignation/Timeout') return 'Desistência / Timeout'

  if (isMatch.value) {
      const maxMarks = Math.max(props.game.player1_marks || 0, props.game.player2_marks || 0)
      if (maxMarks >= 4) return 'Vitória por Marcas'
      return 'Vitória na Partida'
  } else {
      const maxPoints = Math.max(props.game.player1_points || 0, props.game.player2_points || 0)
      if (maxPoints === 120) return 'Bandeira (120 pts)'
      if (maxPoints >= 91) return 'Capote (>90 pts)'
      return 'Vitória Simples'
  }
}

// Estilização dinâmica para destacar o vencedor (NF4)
const winnerBorder = (playerNum) => {
  if (isWinner(playerNum)) {
    return 'border-emerald-400 shadow-lg scale-105 bg-emerald-50'
  }
  return 'border-slate-100 opacity-60 grayscale-[0.5]'
}

// HELPERS
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('pt-PT', {
    day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// Formata o tempo total de jogo guardado na base de dados
const formatDuration = (seconds) => {
  if (!seconds) return '0s'
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return m > 0 ? `${m}m ${s}s` : `${s}s`
}

// G1: Resolve o URL da foto do utilizador para exibição na auditoria
const getAvatarUrl = (user) => {
  if (user?.photo_url) return user.photo_url
  if (user?.photo_avatar_filename) {
    return `http://localhost:8000/api/files/photos_avatars/${user.photo_avatar_filename}`
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.nickname || user?.name || 'U')}&background=random`
}
</script>
