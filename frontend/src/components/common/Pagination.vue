<template>
  <div v-if="lastPage > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4 border-t border-slate-200">

    <div class="text-sm text-slate-500">
      A mostrar <span class="font-bold text-slate-700">{{ from }}</span> a <span class="font-bold text-slate-700">{{ to }}</span> de <span class="font-bold text-slate-700">{{ total }}</span> resultados
    </div>

    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Paginação">

      <button
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <span class="sr-only">Anterior</span>
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
        </svg>
      </button>

      <template v-for="(page, index) in visiblePages" :key="index">
        <span
          v-if="page === '...'"
          class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300"
        >...</span>

        <button
          v-else
          @click="changePage(page)"
          class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 transition-colors"
          :class="page === currentPage
            ? 'z-10 bg-indigo-600 text-white focus-visible:outline-indigo-600'
            : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50'"
        >
          {{ page }}
        </button>
      </template>

      <button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        <span class="sr-only">Seguinte</span>
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
        </svg>
      </button>

    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'

/**
 * NF8: Gestão de listas paginadas para otimizar tráfego e latência.
 * Útil para gerir o histórico de transações ou jogos em plataformas com muitos dados.
 */
const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, default: 0 },
  from: { type: Number, default: 0 },
  to: { type: Number, default: 0 }
})

// Emissão de evento para que o componente pai possa solicitar novos dados à API RESTful (NF2)
const emit = defineEmits(['page-change'])

/**
 * Valida se a página solicitada é válida antes de emitir o pedido de alteração.
 */
const changePage = (page) => {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('page-change', page)
  }
}

/**
 * NF5: Lógica para calcular as páginas visíveis de forma inteligente.
 * Resolve o problema de ter centenas de botões na interface, exibindo apenas as páginas próximas da atual.
 */
const visiblePages = computed(() => {
  const delta = 1 // Quantidade de páginas adjacentes a mostrar à volta da página atual
  const range = []
  const rangeWithDots = []
  let lastAppended

  // Garante que a primeira página está sempre acessível
  range.push(1)

  // Calcula o intervalo dinâmico com base na página onde o utilizador se encontra
  for (let i = props.currentPage - delta; i <= props.currentPage + delta; i++) {
    if (i < props.lastPage && i > 1) {
      range.push(i)
    }
  }

  // Garante que a última página está sempre acessível
  if (props.lastPage > 1) {
    range.push(props.lastPage)
  }

  // Insere os separadores "..." onde existem saltos na sequência numérica
  for (let i of range) {
    if (lastAppended) {
      if (i - lastAppended === 2) {
        rangeWithDots.push(lastAppended + 1)
      } else if (i - lastAppended !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    lastAppended = i
  }

  return rangeWithDots
})
</script>
