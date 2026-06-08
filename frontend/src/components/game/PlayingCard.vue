<template>
  <div
    class="relative select-none transition-all duration-300 aspect-[2/3] rounded-lg h-full w-auto"
    :class="[
      /* Apenas permite interação se for o turno do jogador e a carta não for do oponente */
      isInteractive && !isFaceDown ? 'cursor-pointer hover:brightness-110' : 'cursor-default',
      isSelected ? '-translate-y-4 z-50' : '',
    ]"
    @click="handleClick"
  >
    <img
      :src="isFaceDown ? '/img/cards/semFace.png' : cardImagePath"
      :alt="isFaceDown ? 'Costas' : `${card?.rank} de ${card?.suit}`"
      class="h-full w-full object-contain"
      loading="eager"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * G3: Componente base para a visualização do baralho de 40 cartas.
 * Gere a tradução de lógica de negócio (Ranks/Suits) para caminhos de ficheiros de imagem.
 */
const props = defineProps({
  card: {
    type: Object,
    default: () => ({ rank: '', suit: '' })
  },
  isFaceDown: {
    type: Boolean,
    default: false // NF7: Utilizado para cartas do oponente e monte
  },
  isInteractive: {
    type: Boolean,
    default: false // G3: Apenas verdadeiro durante o turno do utilizador
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['play'])

/**
 * Mapeamento de naipes para os identificadores de ficheiro:
 * Hearts (Copas) -> c, Diamonds (Ouros) -> o, Spades (Espadas) -> e, Clubs (Paus) -> p.
 */
const suitMap = {
  'Copas': 'c', 'Ouros': 'o', 'Espadas': 'e', 'Paus': 'p',
  'Hearts': 'c', 'Diamonds': 'o', 'Spades': 'e', 'Clubs': 'p'
}

/**
 * G3: Mapeamento de valores seguindo a hierarquia da Bisca.
 * Ás (11 pts), Sete/Manilha (10 pts), Rei (4 pts), Valete (3 pts), Dama (2 pts).
 */
const rankMap = {
  'A': '1',  // Ás
  '7': '7',  // Sete (Bisca/Manilha)
  'K': '13', // Rei
  'J': '11', // Valete
  'Q': '12', // Dama
  '2': '2', '3': '3', '4': '4', '5': '5', '6': '6'
}

/**
 * NF1: Resolve o caminho da imagem de forma reativa.
 * Concatena o naipe e o rank para localizar o asset pré-carregado.
 */
const cardImagePath = computed(() => {
  if (!props.card?.suit || !props.card?.rank) return '/img/cards/semFace.png'
  const s = suitMap[props.card.suit] || 'c'
  const r = rankMap[props.card.rank] || props.card.rank
  return `/img/cards/${s}${r}.png`
})

/**
 * G3: Notifica o componente pai sobre a intenção de jogar a carta.
 * A validade da jogada (assistir ou trunfar) será validada pelo servidor.
 */
const handleClick = () => {
  if (props.isInteractive && !props.isFaceDown) {
    emit('play', props.card.id)
  }
}
</script>
