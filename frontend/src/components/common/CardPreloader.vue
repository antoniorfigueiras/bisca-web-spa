<template>
  <div class="hidden">
    <img v-for="img in allCardImages" :key="img" :src="img" />
  </div>
</template>

<script setup>
/** * Definição dos naipes e valores seguindo as regras da Bisca (G3).
 * Os naipes correspondem a: Copas (c), Ouros (o), Espadas (e) e Paus (p).
 * Os ranks excluem o 8, 9 e 10 para formar o baralho de 40 cartas.
 * As cartas de valor especial (As=1, Sete=7, Dama=11, Valete=12, Rei=13) são incluídas.
 */
const suits = ['c', 'o', 'e', 'p']
const ranks = ['1', '2', '3', '4', '5', '6', '7', '11', '12', '13']

// Array que armazenará os caminhos relativos de todos os assets de imagem
const allCardImages = []

/**
 * Ciclo aninhado para gerar dinamicamente os caminhos de todas as 40 cartas do baralho.
 * Garante que todos os recursos visuais necessários para o jogo estão prontos no cliente (NF1).
 */
suits.forEach(suit => {
  ranks.forEach(rank => {
    allCardImages.push(`/img/cards/${suit}${rank}.png`)
  })
})

/**
 * Inclusão da imagem do verso da carta (semFace).
 * Essencial para representar o monte (stock) e as cartas dos adversários (G3).
 */
allCardImages.push('/img/cards/semFace.png')
</script>

<style scoped>
/* Garante que as imagens carregadas em memória não ocupem espaço no layout
   ou interfiram com a interface visível do utilizador (NF4).
*/
.hidden {
  display: none;
}
</style>
