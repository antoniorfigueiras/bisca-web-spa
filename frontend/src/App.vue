<template>
  <GlobalToaster />

  <CardPreloader v-once />

  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import GlobalToaster from '@/components/common/GlobalToaster.vue'
import CardPreloader from './components/common/CardPreloader.vue'

/**
 * G1: Gestão de Sessão no Root da Aplicação.
 * Utilizamos a AuthStore para garantir a persistência da autenticação (NF7).
 */
const authStore = useAuthStore()

/**
 * NF8: Hidratação do Estado e Reidratação de Sessão.
 * Ao montar a aplicação, verificamos se o token no localStorage é válido.
 * Se for, restauramos o perfil do utilizador e reconectamos aos WebSockets (NF9).
 */
onMounted(async () => {
  // checkAuth() comunica com /api/users/me e, em sucesso, invoca socketService.init()
  await authStore.checkAuth()
})
</script>

<style>
/**
 * NF4: Definições globais de layout e tipografia.
 * Garante que a aplicação escala corretamente para a altura total da viewport (G3).
 */
html, body, #app {
  height: 100%;
  margin: 0;
  padding: 0;
  /* Evita scroll horizontal indesejado em animações de transição de página */
  overflow-x: hidden;
}

body {
  /* Suavização de fontes para melhor legibilidade em browsers WebKit e Gecko */
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
