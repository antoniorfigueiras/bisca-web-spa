<template>
  <div class="flex-1 flex flex-col h-full w-full font-sans overflow-hidden bg-slate-200">

    <header
      class="bg-white border-b border-slate-300 px-4 py-2 shadow-sm flex items-center justify-between z-30 h-16 shrink-0"
    >
      <div class="flex items-center gap-3">
        <span class="text-2xl">🃏</span>
        <div class="flex flex-col">
          <span class="font-bold text-slate-800 leading-none">Bisca DAD</span>
        </div>
      </div>

      <ScoreBoard
        :myScore="myScore"
        :opponentScore="opponentScore"
        :myMarks="myMarks"
        :opponentMarks="opponentMarks"
        :isMatch="isMatch"
        :trumpSuit="trumpSuit"
      />
    </header>

    <main class="flex-1 relative flex items-center justify-center p-2 md:p-4 overflow-hidden">
      <div
        class="w-full h-full max-w-6xl max-h-[85vh] aspect-video bg-emerald-800 rounded-[3rem] border-[10px] border-amber-900 shadow-[0_0_50px_rgba(0,0,0,0.5)] relative flex flex-col overflow-hidden"
      >
        <div
          class="absolute inset-0 opacity-20 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/felt.png')]"
        ></div>

        <div class="h-[30%] w-full flex justify-center items-start z-10">
          <div
            class="transform rotate-180 scale-75 md:scale-90 origin-center h-full -translate-y-1/2 opacity-80 hover:opacity-100 transition-opacity"
          >
            <PlayerHand :cards="opponentHandArray" :isFaceDown="true" />
          </div>
        </div>

        <div class="h-[45%] w-full flex items-center justify-center relative px-4 md:px-20">
          <div class="relative w-full h-full flex items-center justify-center pointer-events-none -translate-y-6">

            <div v-if="tableCards.length === 0" class="border-2 border-dashed border-white/5 rounded-xl aspect-[2/3] h-24 md:h-32 flex items-center justify-center text-white/10 text-[10px] font-black uppercase tracking-widest">
              Vaza
            </div>

            <transition-group name="pop">
              <div
                v-if="opponentPlayedCard"
                :key="'opp-' + opponentPlayedCard.card.code"
                class="absolute left-[25%] md:left-[35%] h-[70%] flex flex-col items-center justify-center"
              >
                <p class="absolute -top-8 left-1/2 -translate-x-1/2 text-white/80 text-[10px] font-black uppercase tracking-widest bg-black/30 px-3 py-1 rounded-full whitespace-nowrap">
                  {{ opponentName }}
                </p>
                <PlayingCard :card="opponentPlayedCard.card" class="shadow-2xl transform rotate-2" />
              </div>

              <div
                v-if="myPlayedCard"
                :key="'me-' + myPlayedCard.card.code"
                class="absolute right-[25%] md:right-[35%] h-[70%] flex flex-col items-center justify-center"
              >
                <PlayingCard :card="myPlayedCard.card" class="shadow-2xl transform -rotate-2" />
                <p class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-white/40 text-[9px] font-bold uppercase tracking-widest">
                  Tu
                </p>
              </div>
            </transition-group>
          </div>

          <div class="absolute right-4 md:right-10 h-full flex items-center z-10">
            <div class="scale-75 md:scale-90 lg:scale-100">
              <TrumpCard :card="trumpCard" :deck-count="deckCount" />
            </div>
          </div>
        </div>

        <div class="h-[30%] w-full flex justify-center items-end pb-4 z-20">
          <PlayerHand :cards="myHand" :isInteractive="isMyTurn" @play-card="handlePlayCard" />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import PlayerHand from './PlayerHand.vue'
import PlayingCard from './PlayingCard.vue'
import ScoreBoard from './ScoreBoard.vue'
import GameTimer from './GameTimer.vue'
import TrumpCard from './TrumpCard.vue'

const props = defineProps({
  game: { type: Object, required: true },
  myID: { type: [String, Number], required: true },
  opponentName: { type: String, default: 'Oponente' },
})

const emit = defineEmits(['play-card', 'resign'])

/**
 * NORMALIZAÇÃO DE DADOS (G3/G4): Garante que IDs de tipos diferentes (String/Number) sejam comparáveis
 */
const myNormalizedID = computed(() => String(props.myID))

const isPlayer1 = computed(() => {
  const p1Id = props.game?.player1?.id || props.game?.player1_user_id
  return String(p1Id) === myNormalizedID.value
})

/**
 * PONTUAÇÃO: A soma dos pontos dos jogadores deve ser sempre 120
 */
const myScore = computed(() =>
  isPlayer1.value ? props.game?.player1_points || 0 : props.game?.player2_points || 0,
)
const opponentScore = computed(() =>
  isPlayer1.value ? props.game?.player2_points || 0 : props.game?.player1_points || 0,
)

/**
 * LÓGICA DE MATCH (G3): Verifica se o jogo atual pertence a uma partida (série de jogos até 4 marcas)
 */
const isMatch = computed(
  () => !!(props.game?.match_id || props.game?.game_match_id || props.game?.isMatch),
)

// Marcas acumuladas na partida
const myMarks = computed(() =>
  isPlayer1.value ? props.game?.player1_marks || 0 : props.game?.player2_marks || 0,
)
const opponentMarks = computed(() =>
  isPlayer1.value ? props.game?.player2_marks || 0 : props.game?.player1_marks || 0,
)

// Elementos do Baralho: Trunfo e contagem de cartas no monte
const trumpSuit = computed(() => props.game?.trumpSuit || '')
const trumpCard = computed(() => props.game?.trumpCard || null)
const deckCount = computed(() => props.game?.deck?.length ?? (props.game?.deckCount || 0))

/**
 * GESTÃO DAS MÃOS (NF9): Minimiza o tamanho das mensagens e garante que apenas o dono vê as suas cartas
 */
const myHand = computed(() => {
  if (!props.game) return []
  return isPlayer1.value
    ? props.game.player1Hand || props.game.player1_hand || []
    : props.game.player2Hand || props.game.player2_hand || []
})

// Mão do Oponente: Representada apenas por versos para evitar exposição de dados (NF7)
const opponentHandArray = computed(() => {
  if (!props.game) return []
  const count = isPlayer1.value
    ? props.game.player2Hand?.length || props.game.player2_hand?.length || 0
    : props.game.player1Hand?.length || props.game.player1_hand?.length || 0
  return Array.from({ length: count }, (_, i) => ({ code: 'semFace', id: `back-${i}` }))
})

/**
 * LÓGICA DA VAZA: Identifica quem jogou cada carta na mesa
 */
const tableCards = computed(() => props.game?.table || [])

const myPlayedCard = computed(() =>
  tableCards.value.find((c) => String(c.playerId) === myNormalizedID.value),
)
const opponentPlayedCard = computed(() =>
  tableCards.value.find((c) => String(c.playerId) !== myNormalizedID.value),
)

/**
 * TURNO E INTERAÇÃO: O servidor é a fonte da verdade para a ordem dos turnos
 */
const isMyTurn = computed(() => {
  const turnId = props.game?.turnPlayerId || props.game?.turn_player_id
  return String(turnId) === myNormalizedID.value
})

const handlePlayCard = (card) => {
  // Apenas emite o evento se for efetivamente a vez do jogador
  if (isMyTurn.value) emit('play-card', card)
}
</script>

<style scoped>
/**
 * NF4: Animação pop-in para proporcionar feedback visual claro ao jogar uma carta
 */
.pop-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.pop-enter-from {
  opacity: 0;
  transform: translateY(100px) scale(0.5);
}

/* Moldura de madeira simulada para a mesa (border amber) */
.border-12 {
  border-width: 12px;
}
</style>
