<template>
  <div class="flex flex-col items-center w-full select-none">

    <div
      class="flex items-center gap-2 px-3 py-1 rounded-full border shadow-sm transition-all duration-300 mb-2"
      :class="statusClasses"
    >
      <span class="relative flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="dotColor"></span>
        <span class="relative inline-flex rounded-full h-2 w-2" :class="dotBaseColor"></span>
      </span>
      <span class="text-[10px] font-black tracking-widest whitespace-nowrap uppercase">
        {{ statusText }}
      </span>
    </div>

    <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden relative shadow-inner">
      <div
        class="h-full transition-all duration-200 ease-linear absolute left-0 top-0"
        :class="barColor"
        :style="{ width: `${percentage}%` }"
      ></div>
    </div>

    <div class="h-4 mt-1 flex items-center justify-center">
      <span v-if="timeLeft <= 5 && timeLeft > 0" class="text-[10px] font-black text-red-500 animate-pulse uppercase tracking-tighter">
        ⚠️ Atenção: {{ timeLeft }}s restantes!
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'

/**
 * G3: Temporizador de jogada.
 * A regra oficial define um limite de 20 segundos por jogada; o incumprimento resulta em derrota.
 */
const props = defineProps({
  turnDeadline: {
    type: Number, // Timestamp absoluto (Unix em ms) enviado via WebSocket
    default: null
  },
  isMyTurn: Boolean,
  opponentName: {
    type: String,
    default: 'Oponente'
  }
})

// --- ESTADO ---
const timeLeft = ref(20)      // Representação em segundos para o texto
const exactTimeLeft = ref(20) // Precisão em milissegundos para animação fluida da barra (NF8)
const maxTime = 20            // Constante baseada nas regras de negócio da Bisca
let timerInterval = null

// --- COMPUTED ---

/**
 * NF4: Estilização dinâmica do badge baseada no estado do turno.
 */
const statusClasses = computed(() => props.isMyTurn
  ? 'bg-green-50 text-green-700 border-green-200 ring-1 ring-green-100'
  : 'bg-orange-50 text-orange-700 border-orange-200'
)

const dotColor = computed(() => props.isMyTurn ? 'bg-green-400' : 'bg-orange-400')
const dotBaseColor = computed(() => props.isMyTurn ? 'bg-green-500' : 'bg-orange-500')
const statusText = computed(() => props.isMyTurn ? 'Teu Turno' : `Vez de ${props.opponentName}`)

/**
 * NF8: Cálculo da percentagem da barra.
 * Usa milissegundos para garantir que a transição de largura parece contínua ao utilizador.
 */
const percentage = computed(() => {
  const pct = (exactTimeLeft.value / maxTime) * 100
  return Math.max(0, Math.min(100, pct))
})

/**
 * NF5: Feedback cromático baseado no tempo restante.
 * Verde/Índigo (Normal) -> Amarelo (Aviso < 10s) -> Vermelho (Crítico < 5s).
 */
const barColor = computed(() => {
  if (timeLeft.value <= 5) return 'bg-red-500'
  if (timeLeft.value <= 10) return 'bg-yellow-500'
  return props.isMyTurn ? 'bg-green-500' : 'bg-indigo-400'
})

// --- LÓGICA DE CONTROLO ---

// Interrompe qualquer instância ativa do temporizador para evitar fugas de memória (NF8)
const stopTimer = () => {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

/**
 * Inicia a contagem decrescente visual comparando o tempo atual com a 'deadline' do servidor.
 * O servidor é a fonte da verdade.
 */
const startVisualTimer = (deadline) => {
  stopTimer()

  const update = () => {
    const now = Date.now()
    const remainingMs = deadline - now

    // Atualiza a representação textual em segundos
    timeLeft.value = Math.max(0, Math.ceil(remainingMs / 1000))

    // Atualiza a precisão da barra suave
    exactTimeLeft.value = Math.max(0, remainingMs / 1000)

    if (remainingMs <= 0) {
      stopTimer()
      timeLeft.value = 0
      exactTimeLeft.value = 0
      // Nota: A lógica de derrota é processada no backend via WebSockets
    }
  }

  update() // Execução imediata para evitar atrasos na interface (NF8)

  // Frequência de 100ms para suavidade sem sacrificar performance (NF8)
  timerInterval = setInterval(update, 100)
}

// --- WATCHERS ---

/**
 * Reage a atualizações de rede. Sempre que o servidor define um novo prazo de turno (G3).
 */
watch(() => props.turnDeadline, (newDeadline) => {
  if (newDeadline && newDeadline > Date.now()) {
    startVisualTimer(newDeadline)
  } else {
    stopTimer()
    timeLeft.value = maxTime
    exactTimeLeft.value = maxTime
  }
}, { immediate: true })

// Garante a limpeza do temporizador quando o componente de jogo é destruído (NF1)
onUnmounted(() => {
  stopTimer()
})
</script>
