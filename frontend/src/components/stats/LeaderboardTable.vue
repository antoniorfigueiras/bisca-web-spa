<template>
  <div class="w-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

    <div v-if="isLoading" class="p-8 flex flex-col items-center justify-center text-slate-400">
      <div class="animate-spin h-8 w-8 border-4 border-indigo-100 border-t-indigo-600 rounded-full mb-3"></div>
      <p class="text-sm font-medium animate-pulse">A carregar ranking global...</p>
    </div>

    <div v-else-if="!items || items.length === 0" class="p-8 text-center text-slate-400">
      <span class="text-4xl block mb-2">📉</span>
      <p class="text-sm">Ainda não existem registos para esta categoria.</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider border-b border-slate-100">
          <tr>
            <th class="px-4 py-4 text-center w-20">Posição</th>
            <th class="px-4 py-4">Jogador</th>
            <th class="px-4 py-4 text-right">{{ label }}</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-50">
          <tr
            v-for="(item, index) in items"
            :key="item.id || index"
            class="hover:bg-indigo-50/30 transition-colors group"
            :class="{
              'bg-yellow-50/40': index === 0,
              'bg-slate-50/40': index === 1,
              'bg-orange-50/40': index === 2
            }"
          >
            <td class="px-4 py-4 text-center">
              <div v-if="index === 0" class="text-2xl drop-shadow-sm transform group-hover:scale-110 transition-transform">🥇</div>
              <div v-else-if="index === 1" class="text-2xl drop-shadow-sm">🥈</div>
              <div v-else-if="index === 2" class="text-2xl drop-shadow-sm">🥉</div>
              <div v-else class="text-slate-400 font-mono font-bold text-sm">#{{ index + 1 }}</div>
            </td>

            <td class="px-4 py-4">
              <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-slate-200 border border-slate-200 overflow-hidden shrink-0 shadow-sm">
                  <img
                    :src="getAvatarUrl(item)"
                    class="w-full h-full object-cover"
                    @error="handleImageError"
                  >
                </div>

                <div class="flex flex-col">
                  <span class="font-bold text-slate-700 text-sm truncate max-w-[120px] sm:max-w-xs">
                    {{ item.nickname || 'Jogador Anónimo' }}
                  </span>
                  <span v-if="index === 0" class="text-[9px] text-yellow-600 font-black uppercase tracking-tighter leading-none">
                    Mestre da Bisca
                  </span>
                </div>
              </div>
            </td>

            <td class="px-4 py-4 text-right">
              <span class="font-mono font-black text-slate-800 text-lg">
                {{ formatValue(item.value) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
/**
 * G4: Componente genérico para visualização de Leaderboards Multiplayer.
 * Suporta a exibição de diferentes métricas (Vitórias, Match Wins, Capotes, Bandeiras).
 */
const props = defineProps({
  items: { type: Array, default: () => [] },
  isLoading: { type: Boolean, default: false },
  label: { type: String, default: 'Vitórias' },
  type: { type: String, default: 'number' }
})

// NF5: Formata o valor exibido conforme o contexto da métrica estatística (ex: Moedas vs Contagem).
const formatValue = (val) => {
  const numericValue = val ?? 0
  if (props.type === 'coins') return `${numericValue} 💰`
  return numericValue
}

/**
 * G1: Resolve o URL para a imagem de perfil do jogador.
 * Caso o utilizador não tenha foto, utiliza um serviço externo para gerar um avatar baseado no nome.
 */
const getAvatarUrl = (user) => {
  if (user.photo_avatar_filename) {
    // Referência ao storage configurado no backend (NF2/NF8).
    return `http://localhost:8000/storage/photos_avatars/${user.photo_avatar_filename}`
  }
  const name = user.nickname || 'U'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&size=128&bold=true`
}

// NF5: Fallback de segurança para falhas no carregamento de imagens de perfil.
const handleImageError = (e) => {
  e.target.src = `https://ui-avatars.com/api/?name=User&background=cbd5e1&size=128`
}
</script>
