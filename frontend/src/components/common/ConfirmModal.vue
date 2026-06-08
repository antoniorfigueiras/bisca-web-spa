<template>
  <Transition name="fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
      @click.self="handleCancel"
    >
      <div
        class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden transform transition-all border-2"
        :class="isDanger ? 'border-red-100' : 'border-indigo-50'"
      >
        <div class="p-6 text-center">
          <div
            class="mx-auto flex h-14 w-14 items-center justify-center rounded-full mb-4"
            :class="isDanger ? 'bg-red-50 text-red-600' : 'bg-indigo-50 text-indigo-600'"
          >
            <svg v-if="isDanger" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 9v4"/><path d="M12 17h.01"/>
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>
            </svg>
          </div>

          <h3 class="text-xl font-bold text-slate-900 mb-2">
            {{ title }}
          </h3>

          <p class="text-sm text-slate-500 leading-relaxed px-4">
            {{ message }}
          </p>
        </div>

        <div class="flex border-t border-slate-100 bg-slate-50 p-4 gap-3">
          <button
            @click="handleCancel"
            class="flex-1 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-colors shadow-sm text-sm"
          >
            {{ cancelText }}
          </button>

          <button
            @click="handleConfirm"
            class="flex-1 px-4 py-2.5 font-bold rounded-lg text-white shadow-md transition-transform active:scale-95 text-sm"
            :class="isDanger ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-500'"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
/**
 * NF5: Componente de confirmação modal fundamental para a usabilidade.
 * Implementa o requisito de "confirmação explícita" mencionado para remoção de contas.
 */
defineProps({
  // Controla a visibilidade do componente
  isOpen: {
    type: Boolean,
    default: false
  },
  // Título em destaque no topo do modal
  title: {
    type: String,
    default: 'Tens a certeza?'
  },
  // Texto explicativo sobre as consequências da ação
  message: {
    type: String,
    default: 'Esta ação não pode ser revertida.'
  },
  // Rótulo do botão de ação principal
  confirmText: {
    type: String,
    default: 'Confirmar'
  },
  // Rótulo do botão de desistência
  cancelText: {
    type: String,
    default: 'Cancelar'
  },
  // Determina se o modal deve utilizar cores de alerta (ex: remoção/bloqueio)
  isDanger: {
    type: Boolean,
    default: false
  }
})

// Eventos emitidos para o componente pai gerir o estado e a lógica de negócio
const emit = defineEmits(['confirm', 'close'])

// Fecha o modal sem realizar a ação pretendida
const handleCancel = () => {
  emit('close')
}

// Notifica o componente pai de que o utilizador confirmou a intenção
const handleConfirm = () => {
  emit('confirm')
}
</script>

<style scoped>
/* NF4: Animações de opacidade para evitar transições bruscas e melhorar a fluidez visual */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
