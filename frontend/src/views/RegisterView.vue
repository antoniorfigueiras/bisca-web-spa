<template>
  <div class="register-page-wrapper">
    <div class="flex min-h-[90vh] items-center justify-center bg-slate-50 p-4 py-8 font-sans">
      <Card
        class="w-full max-w-lg shadow-2xl border-t-4 border-t-indigo-600 bg-white animate-in zoom-in-95 duration-300">

        <CardHeader class="space-y-1 text-center">
          <div class="flex justify-center mb-4">
            <div class="bg-indigo-600 text-white p-3 rounded-full shadow-lg">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>
          </div>
          <CardTitle class="text-2xl font-black text-slate-900 tracking-tight">Criar Nova Conta</CardTitle>
          <CardDescription class="font-medium text-slate-500">
            Junta-te à comunidade e ganha <span class="text-indigo-600 font-bold">10 moedas</span> de bónus!
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleRegister" class="space-y-4">
            <div class="flex flex-col items-center gap-4 mb-6">
              <div class="relative group cursor-pointer" @click="triggerFileInput" title="Escolher Foto">
                <div
                  class="w-24 h-24 rounded-full border-4 border-slate-100 overflow-hidden bg-slate-50 shadow-inner flex items-center justify-center transition-all group-hover:border-indigo-200">
                  <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover" />
                  <span v-else class="text-4xl text-slate-300">📷</span>
                </div>
                <div
                  class="absolute inset-0 bg-indigo-600/20 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <span
                    class="text-indigo-900 text-[10px] font-black uppercase bg-white/90 px-2 py-1 rounded">Alterar</span>
                </div>
              </div>
              <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleFileChange" />
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Foto de Perfil (Opcional)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label class="text-xs font-black uppercase tracking-tighter text-slate-500">Nome Próprio</Label>
                <Input v-model="form.name" required placeholder="Ex: João Silva" class="h-11 border-slate-200" />
                <p v-if="errors.name" class="text-[10px] text-red-500 font-bold uppercase">{{ errors.name[0] }}</p>
              </div>
              <div class="space-y-2">
                <Label class="text-xs font-black uppercase tracking-tighter text-slate-500">Nickname (Público)</Label>
                <Input v-model="form.nickname" required placeholder="Ex: BiscaPro" class="h-11 border-slate-200" />
                <p v-if="errors.nickname" class="text-[10px] text-red-500 font-bold uppercase">{{ errors.nickname[0] }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <Label class="text-xs font-black uppercase tracking-tighter text-slate-500">Endereço de Email</Label>
              <Input type="email" v-model="form.email" required placeholder="joao@exemplo.com"
                class="h-11 border-slate-200" />
              <p v-if="errors.email" class="text-[10px] text-red-500 font-bold uppercase">{{ errors.email[0] }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label class="text-xs font-black uppercase tracking-tighter text-slate-500">Password</Label>
                <Input type="password" v-model="form.password" required class="h-11 border-slate-200" />
                <p v-if="errors.password" class="text-[10px] text-red-500 font-bold uppercase">{{ errors.password[0] }}
                </p>
              </div>
              <div class="space-y-2">
                <Label class="text-xs font-black uppercase tracking-tighter text-slate-500">Confirmar</Label>
                <Input type="password" v-model="form.password_confirmation" required class="h-11 border-slate-200" />
              </div>
            </div>

            <div v-if="generalError"
              class="p-4 bg-red-50 text-red-600 text-xs font-bold rounded-lg border border-red-100 animate-shake">
              <div class="flex items-start gap-2">
                <span>⚠️</span>
                <div>
                  <p>{{ generalError }}</p>
                  <button v-if="emailToRestore" @click="showRestoreModal = true" type="button"
                    class="block font-black underline mt-2 uppercase tracking-tighter hover:text-red-800 transition-colors">
                    Sim, quero recuperar a minha conta antiga
                  </button>
                </div>
              </div>
            </div>

            <Button type="submit"
              class="w-full bg-slate-900 hover:bg-black text-white font-black py-7 mt-4 shadow-xl transition-all active:scale-95 disabled:opacity-50"
              :disabled="isLoading">
              <span v-if="isLoading" class="flex items-center gap-2">
                <span class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                A PROCESSAR...
              </span>
              <span v-else class="uppercase tracking-widest text-sm">Registar e Jogar 🚀</span>
            </Button>
          </form>
        </CardContent>

        <CardFooter class="justify-center border-t p-6 bg-slate-50 rounded-b-lg">
          <p class="text-xs text-slate-500 text-center font-medium">
            Já fazes parte da comunidade?
            <router-link to="/login"
              class="font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-tighter ml-1">
              Entra aqui
            </router-link>
          </p>
        </CardFooter>
      </Card>
    </div>

    <div v-if="showRestoreModal"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 animate-in fade-in duration-200">
      <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 border-t-4 border-indigo-600 text-center">
        <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
            <path d="M3 3v5h5" />
          </svg>
        </div>
        <h3 class="text-xl font-black text-slate-900 mb-2 uppercase tracking-tight">Recuperar Perfil</h3>
        <p class="text-sm text-slate-500 mb-6 font-medium">Insere a tua palavra-passe antiga para reativar a conta
          associada a <br><span class="text-indigo-600 font-bold">{{ emailToRestore }}</span>.</p>

        <div class="space-y-4">
          <Input v-model="restorePassword" type="password" placeholder="Palavra-passe antiga"
            class="h-12 text-center" />

          <div class="flex gap-3">
            <Button @click="showRestoreModal = false" variant="outline" class="flex-1 font-bold h-11">CANCELAR</Button>
            <Button @click="confirmRestore" :disabled="isLoading"
              class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold h-11">
              {{ isLoading ? 'A REATIVAR...' : 'RECUPERAR' }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useAPIStore } from '@/services/api'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()
const apiStore = useAPIStore()

// --- ESTADOS ---
const isLoading = ref(false)
const generalError = ref('')
const errors = ref({})
const fileInput = ref(null)
const previewUrl = ref(null)

const showRestoreModal = ref(false)
const restorePassword = ref('')
const emailToRestore = ref('')

const form = reactive({
  name: '',
  nickname: '',
  email: '',
  password: '',
  password_confirmation: '',
  file: null
})

const triggerFileInput = () => fileInput.value.click()

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.file = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

/**
 * G1: Processa o pedido de registo.
 * NF2: Utiliza FormData para suportar o envio de ficheiros binários (foto).
 */
const handleRegister = async () => {
  isLoading.value = true
  generalError.value = ''
  errors.value = {}
  emailToRestore.value = ''

  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('nickname', form.nickname)
  formData.append('email', form.email)
  formData.append('password', form.password)
  formData.append('password_confirmation', form.password_confirmation)
  if (form.file) formData.append('photo_file', form.file)

  try {
    await authStore.register(formData)
    toastStore.showSuccess('Conta criada! Bem-vindo ao Bisca DAD.')

    // Efetua login automático para garantir o bónus de 10 moedas
    if (!authStore.isLoggedIn) {
      await authStore.login({ email: form.email, password: form.password })
    }
    router.push('/')
  } catch (error) {
    /**
     * Captura o erro 409 (Conflict): Indica que o email já pertence a uma conta apagada.
     * Oferece ao utilizador a opção de recuperar o perfil antigo.
     */
    if (error.response?.status === 409 && error.response.data.restore_available) {
      emailToRestore.value = error.response.data.email
      generalError.value = error.response.data.message
    } else if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      generalError.value = 'Existem erros de validação no formulário.'
    } else {
      generalError.value = error.response?.data?.message || 'Erro ao comunicar com o servidor.'
    }
  } finally {
    isLoading.value = false
  }
}

/**
 * G1: Confirma o restauro da conta.
 */
const confirmRestore = async () => {
  if (!restorePassword.value) return

  isLoading.value = true
  try {
    await apiStore.post('/auth/restore', {
      email: emailToRestore.value,
      password: restorePassword.value
    })

    showRestoreModal.value = false
    toastStore.showSuccess('Perfil recuperado com sucesso! Agora já podes entrar.')
    router.push('/login')
  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Palavra-passe incorreta.'
    toastStore.showError(errorMsg)
  } finally {
    isLoading.value = false
  }
}
</script>
