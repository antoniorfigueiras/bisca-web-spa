<template>
  <Teleport to="body">
    <div
      aria-live="assertive"
      class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-9999"
    >
      <div class="flex w-full flex-col items-center space-y-4 sm:items-end">

        <TransitionGroup
          enter-active-class="transform ease-out duration-300 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-for="toast in toastStore.toasts"
            :key="toast.id"
            class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 border-l-4"
            :class="getTypeClass(toast.type)"
          >
            <div class="p-4">
              <div class="flex items-start">

                <div class="shrink-0">
                  <svg v-if="toast.type === 'success'" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <svg v-if="toast.type === 'error'" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                  </svg>
                  <svg v-if="toast.type === 'warning'" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.008v.008H12v-.008z" />
                  </svg>
                  <svg v-if="toast.type === 'info'" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                  </svg>
                </div>

                <div class="ml-3 w-0 flex-1 pt-0.5">
                  <p class="text-sm font-bold text-slate-900">{{ toast.title }}</p>
                  <p class="mt-1 text-sm text-slate-600">{{ toast.message }}</p>
                </div>

                <div class="ml-4 flex shrink-0">
                  <button
                    type="button"
                    @click="toastStore.remove(toast.id)"
                    class="inline-flex rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  >
                    <span class="sr-only">Fechar</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                  </button>
                </div>

              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'

/**
 * Gestão de estado global das notificações através da Pinia Store.
 * Permite despoletar avisos de qualquer parte da SPA (NF1).
 */
const toastStore = useToastStore()

/**
 * NF4/NF5: Helper para aplicar cores de borda consistentes com o tipo de mensagem.
 * Melhora a acessibilidade visual ao permitir distinguir o tipo de alerta apenas pela cor lateral.
 */
const getTypeClass = (type) => {
  switch (type) {
    case 'success': return 'border-green-500' // Sucesso em ações
    case 'error': return 'border-red-500'   // Erros de sistema ou regras
    case 'warning': return 'border-yellow-500' // Avisos de tempo ou limites
    case 'info': return 'border-blue-500'    // Informações gerais da plataforma
    default: return 'border-slate-500'
  }
}
</script>
