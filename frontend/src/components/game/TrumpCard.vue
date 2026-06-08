<template>
  <div class="relative flex items-center justify-center w-full h-full p-4">
    <div class="relative w-24 h-36 md:w-28 md:h-40 flex items-center justify-center group">

      <div
        v-if="card"
        class="absolute -left-8 md:-left-12 rotate-90 z-0 transition-transform duration-500 group-hover:scale-105"
      >
        <PlayingCard
          :card="card"
          :is-face-down="false"
          class="w-20 h-28 md:w-24 md:h-36 opacity-90 drop-shadow-md"
        />
      </div>

      <div v-if="deckCount > 0" class="relative z-10 w-full h-full">
        <div
          v-for="n in Math.min(4, deckCount)"
          :key="n"
          class="absolute inset-0 transition-transform duration-300"
          :style="{
            transform: `translate(${-n * 1.5}px, ${-n * 1.5}px)`,
            zIndex: 10 + n
          }"
        >
          <img
            src="/img/cards/semFace.png"
            class="w-full h-full object-contain drop-shadow-[2px_4px_6px_rgba(0,0,0,0.3)]"
            alt="Verso"
          />
        </div>

        <div class="absolute -top-3 -right-3 z-50">
          <div class="bg-indigo-600 text-white text-[11px] font-black w-8 h-8 rounded-full shadow-lg border-2 border-white flex items-center justify-center animate-in zoom-in duration-300">
            {{ deckCount }}
          </div>
        </div>
      </div>

      <transition name="fade">
        <div
          v-if="deckCount === 0"
          class="absolute inset-0 border-2 border-dashed border-white/20 rounded-xl flex items-center justify-center bg-black/10 backdrop-blur-[1px] z-10"
        >
          <span class="text-[10px] text-white/30 font-black uppercase tracking-widest">Vazio</span>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import PlayingCard from './PlayingCard.vue'

/**
 * G3: Gere a visualização do Trunfo e do Stock conforme as regras oficiais da Bisca.
 * O vencedor de cada vaza compra a primeira carta do monte, seguido pelo perdedor.
 * NF9: Otimização de performance através de propriedades simples para comunicação em tempo real.
 */
defineProps({
  // Objeto que representa a carta virada para trunfo
  card: {
    type: Object,
    default: null,
  },
  // Quantidade de cartas restantes no monte
  deckCount: {
    type: Number,
    default: 0,
  },
})
</script>

<style scoped>
/* NF4: Transição suave para estados de vazio no baralho (NF5) */
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Mantém o alinhamento visual durante a interação do utilizador (NF4) */
.group:hover .relative {
  transform: scale(1.02);
}
</style>
