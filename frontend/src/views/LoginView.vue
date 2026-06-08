<template>
  <div class="flex min-h-[80vh] items-center justify-center bg-slate-50 p-4">
    <Card class="w-full max-w-md shadow-xl border-t-4 border-t-indigo-600 bg-white animate-in zoom-in-95 duration-300">

      <CardHeader class="space-y-1">
        <div class="flex justify-center mb-4">
          <div class="bg-slate-900 text-white p-3 rounded-xl shadow-md transform -rotate-6 transition-transform hover:rotate-0">
            <span class="text-3xl font-bold">♠</span>
          </div>
        </div>
        <CardTitle class="text-2xl font-black text-center text-slate-900 tracking-tight">Entrar na Mesa</CardTitle>
        <CardDescription class="text-center font-medium">
          Introduz as tuas credenciais para aceder ao lobby.
        </CardDescription>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="handleLogin" class="space-y-4">

          <div class="space-y-2">
            <Label for="email" class="text-xs font-black uppercase tracking-widest text-slate-500">Endereço de Email</Label>
            <Input
              id="email"
              type="email"
              v-model="credentials.email"
              placeholder="exemplo@email.com"
              required
              class="h-12 bg-slate-50 focus:bg-white border-slate-200 transition-all"
            />
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <Label for="password" class="text-xs font-black uppercase tracking-widest text-slate-500">Password</Label>
            </div>
            <Input
              id="password"
              type="password"
              v-model="credentials.password"
              placeholder="••••••••"
              required
              class="h-12 bg-slate-50 focus:bg-white border-slate-200 transition-all"
            />
          </div>

          <div v-if="errorMessage" class="p-3 text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-lg flex items-center gap-2 animate-in fade-in slide-in-from-top-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ errorMessage }}
          </div>

          <Button
            type="submit"
            class="w-full bg-slate-900 hover:bg-black text-white font-black py-6 shadow-lg transition-all active:scale-95 disabled:opacity-50"
            :disabled="isLoading"
          >
            <span v-if="isLoading" class="flex items-center gap-2">
              <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
              A VALIDAR...
            </span>
            <span v-else class="uppercase tracking-widest text-sm">Entrar Agora</span>
          </Button>
        </form>
      </CardContent>

      <CardFooter class="justify-center border-t p-6 bg-slate-50 rounded-b-lg">
        <div class="text-xs text-slate-500 text-center font-medium">
          Ainda não tens conta?
          <router-link
            to="/register"
            class="block mt-1 font-black text-indigo-600 hover:text-indigo-800 transition-colors uppercase tracking-tighter"
          >
            Regista-te e ganha 10 moedas de bónus!
          </router-link>
        </div>
      </CardFooter>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

// UI Components
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toastStore = useToastStore()

const credentials = ref({
  email: '',
  password: ''
})

const isLoading = ref(false)
const errorMessage = ref('')

/**
 * G1: Processa o pedido de login através da AuthStore.
 * NF7: Garante a persistência do token JWT no localStorage para reidratação.
 */
const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    // Ação da store que lida com o Axios e estabelece a ligação WebSocket (NF9)
    await authStore.login(credentials.value)

    // Feedback Visual imediato (NF5)
    toastStore.showSuccess(`Bem-vindo, ${authStore.user.nickname}!`)

    /**
     * Redirecionamento Inteligente:
     * Se o utilizador tentou aceder a uma página protegida (ex: /lobby) e foi impedido,
     * o router encaminha-o para esse destino após a autenticação bem-sucedida.
     */
    const redirectPath = route.query.redirect || '/'
    router.push(redirectPath)

  } catch (error) {
    console.error("Autenticação falhou:", error)

    /**
     * G1/G5: Tratamento de erros detalhado.
     * Distingue entre credenciais inválidas (401) e contas suspensas por moderação (403).
     */
    if (error.response?.status === 401) {
      errorMessage.value = 'Credenciais incorretas. Tenta de novo.'
    } else if (error.response?.status === 403 && error.response?.data?.blocked) {
      errorMessage.value = 'A tua conta foi bloqueada por um administrador.'
    } else {
      errorMessage.value = error.response?.data?.message || 'Erro de ligação ao servidor.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>
