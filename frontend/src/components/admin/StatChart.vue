<template>
  <div class="stat-chart-container w-full bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col h-full">

    <div class="mb-6 flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-slate-800">{{ title }}</h3>
        <p v-if="subtitle" class="text-sm text-slate-500">{{ subtitle }}</p>
      </div>
      <div class="chart-actions">
        <slot name="actions"></slot>
      </div>
    </div>

    <div class="relative flex-1 min-h-[300px] w-full">
      <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10 rounded-lg">
        <div class="animate-spin h-8 w-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full"></div>
      </div>

      <div v-else-if="isEmpty" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
        <span class="text-4xl mb-2">📊</span>
        <p class="text-sm font-medium">Sem dados estatísticos disponíveis.</p>
      </div>

      <component
        :is="chartComponent"
        v-if="!isEmpty"
        :data="chartData"
        :options="computedOptions"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler
} from 'chart.js'
import { Bar, Line, Doughnut } from 'vue-chartjs'

// Registo manual de módulos do Chart.js para reduzir o tamanho final do bundle (NF8)
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  PointElement,
  LineElement,
  ArcElement,
  Filler
)

// Definição das propriedades para permitir a reutilização do componente em diferentes métricas (G6)
const props = defineProps({
  type: {
    type: String,
    default: 'bar',
    validator: (value) => ['bar', 'line', 'doughnut'].includes(value)
  },
  title: {
    type: String,
    default: 'Estatística'
  },
  subtitle: {
    type: String,
    default: ''
  },
  labels: {
    type: Array,
    default: () => []
  },
  datasets: {
    type: Array,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  }
})

// Mapeia o tipo de gráfico para o componente correspondente da biblioteca vue-chartjs
const chartComponent = computed(() => {
  switch (props.type) {
    case 'line': return Line
    case 'doughnut': return Doughnut
    default: return Bar
  }
})

// Lógica para validar se existem dados reais para apresentar, evitando gráficos vazios
const isEmpty = computed(() => {
  return !props.datasets ||
         props.datasets.length === 0 ||
         props.datasets.every(ds => !ds.data || ds.data.length === 0)
})

// Formata os dados para o padrão do Chart.js, aplicando cores de fallback (NF4)
const chartData = computed(() => ({
  labels: props.labels,
  datasets: props.datasets.map(ds => ({
    ...ds,
    backgroundColor: ds.backgroundColor || '#6366f1',
    borderColor: ds.borderColor || '#4f46e5',
    borderWidth: ds.borderWidth || 2
  }))
}))

// Define configurações globais de estilo e comportamento para garantir usabilidade (NF5/NF9)
const computedOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      // Exibe legenda fixa apenas em gráficos circulares para facilitar a leitura
      display: props.type === 'doughnut',
      position: 'bottom',
      labels: {
        usePointStyle: true,
        padding: 20,
        font: { family: "'Inter', sans-serif", size: 12 }
      }
    },
    tooltip: {
      // Estilização do tooltip para alinhar com o design moderno da plataforma (NF4)
      backgroundColor: '#1e293b',
      padding: 12,
      cornerRadius: 8,
      titleFont: { family: "'Inter', sans-serif", size: 13, weight: 'bold' },
      bodyFont: { family: "'Inter', sans-serif", size: 12 },
      displayColors: props.type !== 'doughnut'
    }
  },
  // Configuração das escalas (eixos) apenas se não for um gráfico de Doughnut
  scales: props.type === 'doughnut' ? {} : {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: {
        font: { size: 11 },
        color: '#64748b',
        precision: 0 // Garante números inteiros para métricas como número de jogos/transações (G6)
      }
    },
    x: {
      grid: { display: false },
      ticks: { font: { size: 11 }, color: '#64748b' }
    }
  },
  elements: {
    // Configurações estéticas para linhas e barras para suavizar a visualização
    line: { tension: 0.35, fill: props.type === 'line' },
    bar: { borderRadius: 6, columnThickness: 10 }
  }
}))
</script>
