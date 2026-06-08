<template>
  <div class="flex items-center gap-2 md:gap-4 select-none">

    <div class="hidden md:flex items-center gap-3 bg-slate-900 text-white px-3 py-2 rounded-lg shadow-md min-w-[110px]">
      <div class="text-2xl leading-none" :class="isRedSuit ? 'text-red-500' : 'text-blue-400'">
        {{ suitIcon }}
      </div>
      <div class="flex flex-col leading-tight">
        <span class="text-[9px] uppercase font-bold text-slate-400">Trunfo</span>
        <span class="font-bold text-xs truncate">{{ trumpSuit || '---' }}</span>
      </div>
    </div>

    <div class="flex items-stretch bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

      <div v-if="isMatch" class="flex flex-col justify-center gap-1.5 bg-slate-50 px-3 border-r border-slate-100">
        <div class="flex flex-col items-center">
          <span class="text-[8px] font-black uppercase text-slate-400 mb-1">Marcas</span>
          <div class="flex gap-1">
             <div class="flex flex-col gap-1">
                <div v-for="i in 4" :key="`op-mark-${i}`"
                  class="w-3 h-1.5 rounded-sm transition-all duration-500"
                  :class="i <= opponentMarks ? 'bg-slate-600' : 'bg-slate-200'">
                </div>
             </div>
             <div class="w-px bg-slate-200 mx-0.5"></div>
             <div class="flex flex-col gap-1">
                <div v-for="i in 4" :key="`my-mark-${i}`"
                  class="w-3 h-1.5 rounded-sm transition-all duration-500"
                  :class="i <= myMarks ? 'bg-indigo-600' : 'bg-slate-200'">
                </div>
             </div>
          </div>
        </div>
      </div>

      <div class="flex items-center px-4 py-2 gap-6">
        <div class="flex flex-col items-center">
          <span class="text-[9px] uppercase font-bold text-slate-400 leading-none mb-1">Oponente</span>
          <span class="text-2xl font-mono font-black text-slate-600 leading-none">{{ opponentScore }}</span>
        </div>

        <div class="flex items-center justify-center">
          <span class="text-slate-300 font-light text-xl">vs</span>
        </div>

        <div class="flex flex-col items-center">
          <span class="text-[9px] uppercase font-bold text-indigo-700/70 leading-none mb-1">Tu</span>
          <span class="text-2xl font-mono font-black text-indigo-600 leading-none">{{ myScore }}</span>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * G3: Gestão de pontuação e estado da partida.
 * NF5: Componente reativo que reflete o estado enviado pelo servidor via WebSockets.
 */
const props = defineProps({
  // Pontos acumulados no jogo atual (0-120)
  myScore: { type: Number, default: 0 },
  opponentScore: { type: Number, default: 0 },
  // Marcas acumuladas na partida (0-4)
  myMarks: { type: Number, default: 0 },
  opponentMarks: { type: Number, default: 0 },
  // Nome do naipe de trunfo
  trumpSuit: { type: String, default: '' },
  // Booleano que indica se o contexto atual é uma partida completa
  isMatch: { type: Boolean, default: false }
})

/**
 * NF4: Lógica para determinação da cor do ícone do naipe.
 * Melhora a usabilidade permitindo identificar o naipe pela cor convencional (Vermelho/Preto).
 */
const isRedSuit = computed(() => ['Copas', 'Ouros', 'Hearts', 'Diamonds'].includes(props.trumpSuit))

/**
 * NF5: Mapeamento de texto para glifos de naipes.
 * Suporta localização para PT e EN conforme as preferências do utilizador.
 */
const suitIcon = computed(() => {
  const icons = {
    'Copas': '♥', 'Hearts': '♥',
    'Ouros': '♦', 'Diamonds': '♦',
    'Espadas': '♠', 'Spades': '♠',
    'Paus': '♣', 'Clubs': '♣'
  }
  return icons[props.trumpSuit] || '?'
})
</script>
