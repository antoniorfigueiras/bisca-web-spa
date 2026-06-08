<template>
  <div class="flex min-h-[90vh] items-center justify-center bg-slate-50 p-4 py-8 font-sans">
    <Card
      class="w-full max-w-md shadow-lg border-t-4 relative bg-white animate-in fade-in duration-500"
      :class="user.type === 'A' ? 'border-t-slate-900' : 'border-t-indigo-600'"
    >
      <CardHeader class="flex flex-col items-center pb-2">
        <div class="relative h-32 w-32 mb-4 group">
          <img
            :src="displayPhotoUrl"
            alt="Avatar"
            class="h-full w-full rounded-full object-cover border-4 border-white shadow-lg bg-slate-100 transition-transform duration-300"
            :class="isEditing ? 'group-hover:scale-105' : ''"
            @error="handleImageError"
          />

          <template v-if="isEditing">
            <label
              class="absolute bottom-0 right-0 bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-full cursor-pointer shadow-md transition-all border-2 border-white flex items-center justify-center z-10 hover:scale-110 active:scale-95"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" /><path d="m15 5 4 4" /></svg>
              <input type="file" class="hidden" accept="image/*" @change="handleFileChange" />
            </label>

            <button
              v-if="(user.photo_avatar_filename || previewPhotoUrl) && !deletePhoto"
              @click.prevent="handleDeletePhoto"
              class="absolute bottom-0 left-0 bg-red-600 hover:bg-red-700 text-white p-2.5 rounded-full cursor-pointer shadow-md transition-all border-2 border-white flex items-center justify-center z-10 hover:scale-110"
              title="Remover foto"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18" /><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" /><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" /></svg>
            </button>
          </template>
        </div>

        <div class="text-center space-y-1">
          <CardTitle class="text-2xl font-black text-slate-900 tracking-tight">{{ user.name }}</CardTitle>
          <CardDescription class="text-sm font-bold uppercase tracking-widest h-6">
            <span v-if="user.type === 'A'" class="text-slate-500">🛡️ Administrador</span>
            <span v-else class="text-indigo-600">{{ user.nickname ? `@${user.nickname}` : 'Sem Nickname' }}</span>
          </CardDescription>
        </div>

        <div
          v-if="user.type === 'P'"
          class="mt-4 flex items-center gap-2 bg-yellow-50 px-5 py-2 rounded-full border border-yellow-200 shadow-sm"
        >
          <span class="text-xl">💰</span>
          <span class="font-black text-yellow-700 text-lg font-mono">{{ user.coins_balance ?? 0 }}</span>
        </div>
      </CardHeader>

      <CardContent class="space-y-6 pt-6">
        <form @submit.prevent="saveProfile" class="space-y-4">
          <div class="space-y-1">
            <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nome Completo</Label>
            <div v-if="!isEditing" class="text-slate-700 font-bold bg-slate-50 p-3 rounded-lg border border-slate-200">
              {{ user.name }}
            </div>
            <Input v-else v-model="formData.name" required class="bg-white h-11" />
          </div>

          <div v-if="user.type === 'P'" class="space-y-1">
            <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nickname (Público)</Label>
            <div v-if="!isEditing" class="text-slate-700 font-bold bg-slate-50 p-3 rounded-lg border border-slate-200">
              {{ user.nickname || '-' }}
            </div>
            <Input v-else v-model="formData.nickname" placeholder="Ex: BiscaMaster" class="bg-white h-11" />
          </div>

          <div class="space-y-1">
            <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Endereço de Email</Label>
            <div v-if="!isEditing" class="text-slate-700 font-bold bg-slate-50 p-3 rounded-lg border border-slate-200">
              {{ user.email }}
            </div>
            <Input v-else v-model="formData.email" type="email" required class="bg-white h-11" />
          </div>

          <div v-if="isEditing" class="space-y-1 bg-slate-50 p-4 rounded-xl border border-slate-200 animate-in slide-in-from-top-2">
            <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Alterar Password</Label>
            <Input v-model="formData.password" type="password" placeholder="Mínimo 3 caracteres para alterar" class="bg-white" />
          </div>

          <div class="space-y-1" v-if="formData.password">
            <Label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Confirmar Password</Label>
            <Input v-model="formData.password_confirmation" type="password" placeholder="Repita a password" class="bg-white" />
          </div>
        </form>

        <div v-if="!isEditing" class="pt-4 border-t space-y-3">
          <template v-if="user.type === 'P'">
            <Button variant="outline" class="w-full justify-between font-bold" @click="$router.push('/history/games')">
              <span>📜 Histórico de Jogos</span>
              <span class="text-slate-300">→</span>
            </Button>
            <Button variant="outline" class="w-full justify-between font-bold" @click="$router.push('/history/wallet')">
              <span>🪙 Extrato de Moedas</span>
              <span class="text-slate-300">→</span>
            </Button>
          </template>

          <Button v-else variant="outline" class="w-full justify-between font-bold border-slate-900 text-slate-900" @click="$router.push('/admin')">
            <span>🛡️ Painel de Administração</span>
            <span>→</span>
          </Button>

          <div v-if="user.type === 'P'" class="pt-4 text-center">
            <button
              @click="showDeleteModal = true"
              class="text-slate-400 text-[10px] font-black uppercase tracking-tighter hover:text-amber-600 hover:underline transition-colors"
            >
              Desativar conta
            </button>
          </div>
        </div>
      </CardContent>

      <CardFooter class="flex justify-between border-t p-6 bg-slate-50/50 rounded-b-lg">
        <template v-if="!isEditing">
          <Button variant="ghost" class="font-bold" @click="$router.push('/')">VOLTAR</Button>
          <Button class="bg-indigo-600 text-white hover:bg-indigo-700 font-bold px-8 shadow-md" @click="startEditing">
            EDITAR PERFIL
          </Button>
        </template>
        <template v-else>
          <Button variant="ghost" class="font-bold" @click="cancelEditing">CANCELAR</Button>
          <Button class="bg-emerald-600 text-white hover:bg-emerald-700 font-bold min-w-[140px] shadow-md" @click="saveProfile" :disabled="isLoading">
            {{ isLoading ? 'A GUARDAR...' : 'GUARDAR' }}
          </Button>
        </template>
      </CardFooter>
    </Card>

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="p-8 text-center space-y-4">
          <div class="mx-auto bg-amber-100 w-16 h-16 rounded-full flex items-center justify-center text-amber-600 text-3xl mb-2">🔄</div>
          <h3 class="text-xl font-black text-slate-900 uppercase">Desativar Conta</h3>
          <p class="text-sm text-slate-500 leading-relaxed font-medium">
            O teu saldo de moedas será zerado. O histórico de jogos e transações será preservado para fins estatísticos.
          </p>
          <div class="pt-4">
            <Label class="text-[10px] font-black uppercase text-slate-400 block mb-2">Confirma com a tua password</Label>
            <Input v-model="deletePassword" type="password" placeholder="••••••••" class="text-center h-12 focus:ring-amber-500 border-slate-200" />
          </div>
        </div>
        <div class="bg-slate-50 p-4 flex gap-3">
          <Button variant="outline" @click="closeDeleteModal" class="flex-1 font-bold">CANCELAR</Button>
          <Button class="bg-amber-600 hover:bg-amber-700 text-white flex-1 font-bold border-none" @click="confirmDeleteAccount" :disabled="isDeleting || !deletePassword">
            {{ isDeleting ? 'A PROCESSAR...' : 'CONFIRMAR' }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAPIStore } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import socketService from '@/services/socket'
import { useGameStore } from '@/stores/game'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const authStore = useAuthStore()
const apiStore = useAPIStore()
const toastStore = useToastStore()
const router = useRouter()
const gameStore = useGameStore()

// NF1: Utiliza dados reativos da Pinia Store.
const user = computed(() => authStore.user || {})

// --- ESTADOS LOCAIS ---
const isEditing = ref(false)
const isLoading = ref(false)
const previewPhotoUrl = ref(null)
const newPhotoFile = ref(null)
const deletePhoto = ref(false)
const showDeleteModal = ref(false)
const deletePassword = ref('')
const isDeleting = ref(false)

const formData = reactive({
  name: '',
  nickname: '',
  email: '',
  password: '',
  password_confirmation: '',
})

// NF5: Resolve dinamicamente o URL da imagem (Asset local, Preview ou UI-Avatars).
const displayPhotoUrl = computed(() => {
  if (deletePhoto.value) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value.name)}&background=6366f1&color=fff&bold=true`
  }
  if (previewPhotoUrl.value) return previewPhotoUrl.value
  if (user.value.photo_url) return user.value.photo_url
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value.name)}&background=6366f1&color=fff&bold=true`
})

const handleImageError = (e) => {
  e.target.src = `https://ui-avatars.com/api/?name=User&background=cbd5e1&color=fff`
}

const startEditing = () => {
  formData.name = user.value.name
  formData.nickname = user.value.nickname || ''
  formData.email = user.value.email
  formData.password = ''
  formData.password_confirmation = ''
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
  previewPhotoUrl.value = null
  deletePhoto.value = false
}

/**
 * G1: Processa o ficheiro de imagem selecionado para upload.
 */
const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    newPhotoFile.value = file
    previewPhotoUrl.value = URL.createObjectURL(file)
    deletePhoto.value = false
  }
}

const handleDeletePhoto = () => {
  deletePhoto.value = true
  newPhotoFile.value = null
  previewPhotoUrl.value = null
}

/**
 * G1: Persiste as alterações no servidor via API RESTful.
 * Utiliza FormData para suportar o upload de ficheiros binários (NF2).
 */
const saveProfile = async () => {
  isLoading.value = true
  try {
    const data = new FormData()
    // NF2: Utiliza spoofing de método (_method) para contornar limitações de Multipart no PUT do Laravel.
    data.append('_method', 'PUT')
    data.append('name', formData.name)
    data.append('email', formData.email)
    if (user.value.type === 'P' && formData.nickname) data.append('nickname', formData.nickname)
    if (formData.password) {
      data.append('password', formData.password)
      data.append('password_confirmation', formData.password_confirmation)
    }
    if (newPhotoFile.value) data.append('photo_file', newPhotoFile.value)
    if (deletePhoto.value) data.append('delete_photo', '1')

    const response = await apiStore.post(`users/${user.value.id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    authStore.user = response.data.data || response.data
    isEditing.value = false
    toastStore.showSuccess('Perfil atualizado com sucesso!')
  } catch (error) {
    toastStore.showError(error.response?.data?.message || 'Erro ao guardar dados.')
  } finally {
    isLoading.value = false
  }
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  deletePassword.value = ''
}

/**
 * G1: Processa a desativação da conta (Soft-Delete).
 * NF9: Encerra ligações de rede (WebSockets) antes de invalidar a sessão por segurança.
 */
const confirmDeleteAccount = async () => {
  isDeleting.value = true
  try {
    if (socketService) socketService.disconnect()

    // NF2: Envia o pedido DELETE com confirmação de password conforme requisitos de segurança (NF7).
    await apiStore.delete(`users/${user.value.id}`, {
      data: { password: deletePassword.value },
    })
    authStore.token = null
    authStore.clearSession()
    if (gameStore) gameStore.resetState()

    toastStore.showSuccess('Conta desativada. Poderás restaurá-la no menu de registo.')
    router.push('/login')
  } catch (error) {
    if (error.response?.status === 401) {
      authStore.clearSession()
      toastStore.showSuccess('Conta desativada com sucesso.')
      router.push('/login')
      return
    }
    toastStore.showError(error.response?.data?.message || 'Erro ao desativar conta.')
    if (socketService && !socketService.connected) socketService.init()
  } finally {
    isDeleting.value = false
    showDeleteModal.value = false
  }
}
</script>
