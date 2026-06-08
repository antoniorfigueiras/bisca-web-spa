<template>
  <div
    class="flex items-center justify-center w-full h-full bg-slate-900/60 backdrop-blur-md absolute inset-0 z-50 p-4"
  >
    <Card
      class="w-full max-w-md shadow-2xl border-t-4 border-t-indigo-500 animate-in zoom-in-95 duration-300 bg-white"
    >
      <CardHeader class="text-center space-y-2">
        <div
          class="mx-auto bg-indigo-50 w-16 h-16 rounded-2xl flex items-center justify-center mb-2 rotate-3"
        >
          <span class="text-3xl">💰</span>
        </div>
        <CardTitle class="text-2xl font-black text-slate-900 tracking-tight"
          >Aposta da Partida</CardTitle
        >
        <CardDescription class="text-slate-500 font-medium">
          Cheguem a um acordo antes de começar a Bisca.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6">
        <div
          class="flex flex-col items-center justify-center py-6 bg-slate-50 rounded-3xl border border-slate-100 shadow-inner"
        >
          <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1"
            >Valor em Jogo</span
          >
          <div class="flex items-baseline gap-2">
            <span class="text-6xl font-black text-indigo-600 font-mono tracking-tighter">{{
              currentStake
            }}</span>
            <span class="text-2xl font-bold text-indigo-400">moedas</span>
          </div>
        </div>

        <div
          v-if="incomingProposal"
          class="p-5 bg-amber-50 rounded-2xl border border-amber-100 space-y-4 animate-in fade-in slide-in-from-bottom-4"
        >
          <div class="flex items-center gap-3">
            <span
              class="flex-shrink-0 bg-amber-200 text-amber-700 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
              >!</span
            >
            <p class="text-slate-700 text-sm font-semibold">
              <span class="text-amber-700">{{ opponentName }}</span> propõe subir para
              <span class="font-bold text-indigo-700">{{ incomingProposal }} moedas</span>.
            </p>
          </div>
          <div class="flex gap-3">
            <Button
              variant="outline"
              class="flex-1 rounded-xl border-slate-200 text-slate-600 font-bold"
              @click="$emit('reject-proposal')"
            >
              Manter {{ currentStake }}
            </Button>
            <Button
              class="flex-1 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold shadow-md"
              @click="$emit('accept-proposal', incomingProposal)"
            >
              Aceitar
            </Button>
          </div>
        </div>

        <div
          v-else-if="waitingForOpponent"
          class="text-center py-8 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200"
        >
          <div
            class="animate-spin h-10 w-10 border-4 border-indigo-100 border-t-indigo-600 rounded-full mx-auto mb-4"
          ></div>
          <p class="text-slate-500 text-sm font-bold animate-pulse uppercase tracking-widest">
            A aguardar resposta de {{ opponentName }}...
          </p>
        </div>

        <div v-else-if="!isReady" class="space-y-4">
          <div v-if="!isProposing" class="flex flex-col gap-3">
            <div class="flex gap-3">
              <Button
                v-if="canPropose"
                variant="outline"
                class="flex-1 rounded-2xl border-indigo-100 text-indigo-600 font-bold h-14"
                @click="isProposing = true"
                :disabled="currentStake >= 100"
              >
                Subir Valor
              </Button>

              <Button
                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black h-14 shadow-lg shadow-indigo-200 uppercase tracking-widest text-xs"
                @click="confirmReady"
              >
                Estou Pronto
              </Button>
            </div>
            <p
              v-if="!canPropose"
              class="text-[9px] text-center text-slate-400 font-black uppercase tracking-widest"
            >
              É a vez de {{ opponentName }} propor...
            </p>
          </div>

          <div
            v-else
            class="space-y-4 bg-white p-5 rounded-3xl border border-slate-200 shadow-xl animate-in slide-in-from-top-4"
          >
            <label
              class="text-xs font-black text-slate-400 uppercase tracking-widest block text-center"
              >Nova Aposta (Máx: 100)</label
            >
            <div class="flex gap-3 items-center">
              <Input
                type="number"
                v-model.number="newStakeValue"
                :min="currentStake + 1"
                :max="100"
                class="text-center font-mono font-black text-2xl h-16 rounded-2xl bg-slate-50 border-none focus:ring-2 focus:ring-indigo-500"
              />
              <Button
                variant="ghost"
                class="h-16 w-16 rounded-2xl hover:bg-slate-100"
                @click="isProposing = false"
                >✕</Button
                >
            </div>
            <p
              v-if="newStakeValue > userBalance"
              class="text-[10px] text-red-500 font-black text-center uppercase tracking-tighter"
            >
              ⚠️ Saldo insuficiente (Tens {{ userBalance }} 💰)
            </p>
            <Button
              class="w-full bg-slate-900 text-white font-black h-14 rounded-2xl shadow-lg uppercase tracking-widest text-xs"
              :disabled="!isValidProposal"
              @click="handlePropose"
            >
              Enviar Proposta
            </Button>
          </div>
        </div>

        <div
          v-else
          class="text-center py-10 bg-indigo-600 rounded-[2.5rem] shadow-xl shadow-indigo-200"
        >
          <div class="text-4xl mb-4 animate-bounce">👍</div>
          <p class="text-white font-black uppercase text-sm tracking-[0.2em]">Tudo pronto!</p>
          <p class="text-indigo-100 text-[10px] mt-2 font-medium px-8 opacity-80">
            A partida começará assim que <span class="underline">{{ opponentName }}</span> também
            confirmar.
          </p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'

/**
 * Propriedades recebidas da GameView para refletir o estado do servidor
 */
const props = defineProps({
  // Valor da aposta atual
  currentStake: { type: Number, default: 3 },
  // Saldo atual de moedas do utilizador
  userBalance: { type: Number, required: true },
  opponentName: { type: String, default: 'Oponente' },
  // Valor proposto pelo oponente se houver uma negociação ativa
  incomingProposal: { type: Number, default: null },
  // Estado local de prontidão para iniciar a partida
  isReady: { type: Boolean, default: false },
  // Indica se o turno de negociação pertence a este jogador
  canPropose: { type: Boolean, default: true },
  // Bloqueia interações enquanto aguarda resposta de rede
  waitingForOpponent: { type: Boolean, default: false },
})

const emit = defineEmits(['propose-stake', 'accept-proposal', 'reject-proposal', 'ready'])

// Estado local para o formulário de proposta
const isProposing = ref(false)
const newStakeValue = ref(props.currentStake + 1)

// NF1: Watcher para garantir que o formulário está sempre sincronizado com o estado global da partida
watch(
  () => props.currentStake,
  (newVal) => {
    newStakeValue.value = newVal + 1
    isProposing.value = false
  },
)

/**
 * G3/NF6: Validação rigorosa de proposta baseada nas regras de negócio:
 * 1. O valor deve ser superior ao atual.
 * 2. O valor não pode exceder o limite de 100 moedas.
 * 3. O jogador deve ter moedas suficientes em saldo.
 */
const isValidProposal = computed(() => {
  const val = Number(newStakeValue.value)
  return val > props.currentStake && val <= 100 && val <= props.userBalance
})

/**
 * Envia a nova proposta via emissão para o componente pai gerir o WebSocket.
 */
const handlePropose = () => {
  if (!isValidProposal.value) return
  emit('propose-stake', Number(newStakeValue.value))
  isProposing.value = false
}

const confirmReady = () => {
  emit('ready')
}
</script>
