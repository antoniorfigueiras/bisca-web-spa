<template>
  <Transition name="fade">
    <div
      v-if="fullScreen && visible"
      class="fixed inset-0 z-100 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm"
    >
      <div
        class="animate-spin rounded-full border-slate-200 border-t-indigo-600"
        :class="[sizeClasses, borderClasses]"
      ></div>
      <p v-if="text" class="mt-4 text-sm font-semibold text-slate-600 animate-pulse">
        {{ text }}
      </p>
    </div>

    <div
      v-else-if="visible"
      class="flex flex-col items-center justify-center p-4"
      :class="className"
    >
      <div
        class="animate-spin rounded-full border-slate-200 border-t-indigo-600"
        :class="[sizeClasses, borderClasses]"
      ></div>
      <p v-if="text" class="mt-3 text-xs font-medium text-slate-500">
        {{ text }}
      </p>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'

/**
 * NF5: Componente de feedback para operações assíncronas.
 * Garante que o utilizador percebe que o sistema está a processar dados,
 * minimizando o esforço cognitivo e incerteza (NF5).
 */
const props = defineProps({
  // Controla a visibilidade do indicador de carregamento
  visible: {
    type: Boolean,
    default: true
  },
  /**
   * Texto a exibir. Útil para:
   * - "A validar pagamento..."
   * - "A criar administrador..."
   * - "A carregar estatísticas..."
   */
  text: {
    type: String,
    default: ''
  },
  // Se verdadeiro, o componente ocupa todo o ecrã com um overlay (NF1)
  fullScreen: {
    type: Boolean,
    default: false
  },
  // Define a escala visual do spinner (sm, md, lg, xl) para flexibilidade de design (NF4)
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  // Classes CSS adicionais para customização pontual
  className: {
    type: String,
    default: ''
  }
})

/**
 * NF4: Mapeamento de tamanhos para classes utilitárias do Tailwind.
 * Assegura que o design permanece consistente em toda a aplicação (NF4).
 */
const sizeClasses = computed(() => {
  const mapping = {
    sm: 'h-5 w-5',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
    xl: 'h-16 w-16'
  }
  return mapping[props.size] || mapping.md
})

/**
 * Ajuste da espessura da borda proporcional ao tamanho para harmonia visual (NF4).
 * Melhora a legibilidade do ícone de animação em tamanhos muito pequenos ou grandes.
 */
const borderClasses = computed(() => {
  if (props.size === 'sm') return 'border-2'
  if (props.size === 'xl') return 'border-[5px]'
  return 'border-4'
})
</script>

<style scoped>
/* NF4/NF5: Transição fade definida por CSS para garantir uma animação estável */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
