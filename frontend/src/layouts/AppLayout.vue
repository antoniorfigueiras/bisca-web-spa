<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col">
    <NavBar v-if="!isGamePage" />

    <main
      class="flex-1 w-full mx-auto"
      :class="isGamePage ? 'h-[calc(100vh)]' : 'max-w-[1920px] p-4 md:p-6'"
    >
      <RouterView v-slot="{ Component }">
        <Transition name="page-fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <footer
      v-if="!isGamePage"
      class="py-6 text-center text-[10px] uppercase tracking-widest text-slate-400 border-t border-slate-200 mt-auto bg-white"
    >
      <p>&copy; 2025 Bisca DAD. Projeto Académico para Engenharia Informática[cite: 1].</p>
    </footer>

    <GlobalToaster />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NavBar from '@/components/common/NavBar.vue'
import GlobalToaster from '@/components/common/GlobalToaster.vue'

const route = useRoute()

/**
 * NF4: Verifica se a rota atual corresponde ao ecrã de jogo ativo.
 * Se for verdadeiro, a aplicação utiliza a altura total do ecrã (viewport) para máxima imersão (G3)[cite: 99, 173].
 */
const isGamePage = computed(() => route.name === 'game')
</script>

<style scoped>
/* NF5: Definição de estilos para transições de opacidade entre rotas da SPA[cite: 170, 173]. */
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.25s ease-in-out;
}

.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}
</style>
