<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-900 flex flex-col relative overflow-hidden">

    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-200/20 blur-3xl animate-pulse"></div>
        <div class="absolute top-[40%] -right-[10%] w-[40%] h-[40%] rounded-full bg-blue-200/20 blur-3xl opacity-50"></div>
    </div>

    <header class="w-full py-8 flex justify-center relative z-10">
      <RouterLink to="/" class="flex items-center gap-3 text-3xl font-black text-slate-800 hover:scale-105 transition-transform duration-300 group" title="Voltar à Página Inicial">
        <span class="text-4xl filter drop-shadow-sm group-hover:rotate-12 transition-transform">🃏</span>
        <span class="tracking-tight text-slate-900">Bisca DAD</span>
      </RouterLink>
    </header>

    <main class="flex-1 flex flex-col items-center justify-start sm:justify-center p-4 w-full relative z-10">
      <RouterView v-slot="{ Component }">
        <Transition name="scale-fade" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>

    <footer class="py-6 text-center text-[10px] uppercase tracking-widest font-bold text-slate-400 relative z-10">
      <p>&copy; 2025 Bisca DAD &bull; Politécnico de Leiria</p>
    </footer>

  </div>
</template>

<script setup>
/** * NF1: Implementação seguindo os princípios e boas práticas do Vue.js.
 * Este layout é focado em utilizadores anónimos, que podem apenas jogar partidas single-player contra o bot.
 */
</script>

<style scoped>
/** * NF5: Animação de entrada/saída otimizada para manter o utilizador focado nos fluxos de autenticação (G1).
 * As transições ajudam a criar uma experiência de Single-Page Application (SPA) mais profissional.
 */
.scale-fade-enter-active,
.scale-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.scale-fade-enter-from {
  opacity: 0;
  transform: scale(0.98) translateY(12px);
}

.scale-fade-leave-to {
  opacity: 0;
  transform: scale(1.02) translateY(-12px);
}
</style>
