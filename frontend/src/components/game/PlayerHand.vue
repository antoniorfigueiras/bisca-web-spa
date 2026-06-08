<template>
  <div class="relative flex justify-center items-end h-full w-full z-10 select-none">
    <div
      class="flex transition-all duration-500 ease-out py-2 h-full items-end"
      :class="[
        cards.length > 5 ? '-space-x-8 md:-space-x-12' : '-space-x-4 md:-space-x-6',
        isInteractive ? 'hover:-space-x-2 md:hover:-space-x-4' : '',
      ]"
    >
      <PlayingCard
        v-for="(card, index) in cards"
        :key="card.id || index"
        :card="card"
        :isInteractive="isInteractive"
        :isFaceDown="isFaceDown"
        :style="getCardStyle(index)"
        class="origin-bottom transition-all duration-300 shadow-md h-full"
        :class="[
          /* NF5: Feedback visual (elevação e rotação) ao passar o rato apenas se for o turno do jogador */
          isInteractive
            ? 'hover:rotate-0! hover:-translate-y-[20%]! hover:z-50! hover:shadow-2xl cursor-pointer'
            : 'cursor-default',
        ]"
        @play="onPlayCard"
      />
    </div>
  </div>
</template>

<script setup>
import PlayingCard from './PlayingCard.vue'

/**
 * G3: Gere a exibição das cartas na mão do jogador ou oponente.
 * NF9: Otimiza a comunicação enviando apenas o essencial, suportando "Face Down" para ocultar dados.
 */
const props = defineProps({
  // Array de objetos de carta
  cards: {
    type: Array,
    default: () => []
  },
  // Define se o jogador pode interagir com as cartas (apenas no seu turno)
  isInteractive: {
    type: Boolean,
    default: false
  },
  // NF7: Protege a privacidade ocultando as cartas do adversário
  isFaceDown: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['play-card'])

/**
 * G3: Emite o evento de jogada.
 * A validação das regras (como a obrigatoriedade de seguir o naipe na fase final) é garantida pelo servidor.
 */
const onPlayCard = (card) => {
  if (props.isInteractive) {
    emit('play-card', card)
  }
}

/**
 * NF4/NF5: Calcula a rotação e o arco da mão para realismo visual.
 * Adapta dinamicamente o ângulo para as variantes "Bisca de 3" e "Bisca de 9".
 */
const getCardStyle = (index) => {
  const total = props.cards.length
  if (total === 0) return {}

  const middle = (total - 1) / 2

  // NF4: Rotação: Spread menor para mãos de 9 cartas, maior para mãos de 3 cartas para manter o equilíbrio visual.
  const spread = total > 5 ? 3 : 6
  const rotate = (index - middle) * spread

  // NF4: Efeito de Arco: Simula a curvatura natural de uma mão de cartas real.
  const yOffset = Math.abs(index - middle) * (total > 5 ? 4 : 8)

  return {
    zIndex: index,
    transform: `rotate(${rotate}deg) translateY(${yOffset}px)`
  }
}
</script>
