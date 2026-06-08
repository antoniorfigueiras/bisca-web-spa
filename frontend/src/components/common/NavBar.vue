<template>
  <header class="border-b bg-white sticky top-0 z-30 shadow-sm">
    <div class="container mx-auto flex h-16 items-center justify-between px-4">

      <div class="flex items-center gap-2">
        <span class="text-2xl">🃏</span>
        <RouterLink to="/" class="text-lg font-bold tracking-tight text-slate-800 hover:text-indigo-600 transition-colors">
          Bisca DAD
        </RouterLink>
      </div>

      <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
        <RouterLink to="/" class="transition-colors hover:text-slate-900 text-slate-500" active-class="text-slate-900 font-bold">
          Lobby
        </RouterLink>

        <RouterLink
          v-if="authStore.user?.type === 'A'"
          to="/admin"
          class="transition-colors text-red-600 hover:text-red-800 font-bold flex items-center gap-1"
          active-class="underline"
        >
          <span>🛡️</span> Painel Admin
        </RouterLink>

        <RouterLink to="/leaderboard" class="transition-colors hover:text-slate-900 text-slate-500" active-class="text-slate-900 font-bold">
          Top Jogadores
        </RouterLink>

        <template v-if="authStore.isLoggedIn && authStore.user?.type === 'P'">
          <RouterLink to="/history/games" class="transition-colors hover:text-slate-900 text-slate-500" active-class="text-slate-900 font-bold">
            Meus Jogos
          </RouterLink>
        </template>
      </nav>

      <div class="flex items-center gap-4">

        <div v-if="!authStore.isLoggedIn" class="flex items-center gap-4">
          <RouterLink to="/login" class="text-sm font-medium transition-colors hover:text-indigo-600 text-slate-600">
            Entrar
          </RouterLink>
          <RouterLink to="/register" class="text-sm font-bold bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">
            Registar
          </RouterLink>
        </div>

        <div v-else class="flex items-center gap-4 border-l pl-6 ml-2 border-slate-200">

          <RouterLink to="/profile" class="flex items-center gap-3 hover:bg-slate-50 p-1.5 rounded-lg transition-colors cursor-pointer group">
            <div class="relative h-9 w-9 overflow-hidden rounded-full border border-slate-200 shadow-sm group-hover:border-indigo-300">
              <img :src="userPhotoUrl" alt="Avatar" class="h-full w-full object-cover" @error="handleImageError" />
            </div>
            <div class="hidden sm:flex flex-col items-end justify-center">
              <span class="font-bold text-slate-700 text-xs leading-none group-hover:text-indigo-600">
                {{ userDisplayName }}
              </span>
              <span class="text-[10px] text-slate-400">Ver Perfil</span>
            </div>
          </RouterLink>

          <RouterLink
            v-if="authStore.user?.type === 'P'"
            to="/store"
            class="group flex items-center gap-1 bg-yellow-50 px-3 py-1.5 rounded-full border border-yellow-200 hover:bg-yellow-100 transition-colors shadow-sm"
          >
            <span class="text-xs font-bold text-yellow-700">
              {{ authStore.user?.coins_balance ?? 0 }}
            </span>
            <span class="text-[10px]">💰</span>
            <span class="text-[10px] text-yellow-600 font-bold ml-1 opacity-0 group-hover:opacity-100">+</span>
          </RouterLink>

          <button @click="handleLogout" class="text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors p-2 rounded-md" title="Sair">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" x2="9" y1="12" y2="12" /></svg>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import socketService from '@/services/socket'

const authStore = useAuthStore()
const router = useRouter()

/**
 * NF8: Inicialização antecipada de serviços para otimizar performance.
 * Verifica a sessão atual e estabelece a ligação WebSocket para tempo-real.
 */
onMounted(() => {
  authStore.checkAuth()
  socketService.init()
})

/**
 * Prioriza o nickname para exibição social em jogos e tabelas.
 */
const userDisplayName = computed(() => {
  const user = authStore.user
  if (!user) return 'Convidado'
  return (user.nickname && user.nickname !== 'null') ? user.nickname : user.name
})

/**
 * G1: Resolve o URL da imagem de perfil.
 * Segue a hierarquia: URL da API -> Servidor de Storage -> Gerador de Avatares (Fallback).
 */
const userPhotoUrl = computed(() => {
  const user = authStore.user

  // 1. URL completo fornecido pela API RESTful
  if (user?.photo_url) return user.photo_url

  // 2. Construção manual via storage local se disponível
  if (user?.photo_avatar_filename) {
    return `http://localhost:8000/storage/photos_avatars/${user.photo_avatar_filename}`
  }

  // 3. Fallback visual para garantir usabilidade (NF5)
  const name = user?.nickname || user?.name || 'User'
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6366f1&color=fff&bold=true`
})

/**
 * NF5: Tratamento de erro de carregamento de imagem para manter a estética da interface.
 */
const handleImageError = (e) => {
  e.target.src = `https://ui-avatars.com/api/?name=User&background=cbd5e1&color=fff`
}

/**
 * G1: Encerra a sessão do utilizador e desliga comunicações ativas.
 */
const handleLogout = () => {
  authStore.logout()
  socketService.disconnect()
  router.push('/login')
}
</script>
