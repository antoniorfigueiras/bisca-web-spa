<template>
  <div class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <h2 class="text-2xl font-bold text-slate-800">Gestão de Utilizadores</h2>

      <div class="flex gap-3 w-full sm:w-auto">
        <div class="relative flex-1 sm:w-64">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
          <Input
            v-model="searchQuery"
            placeholder="Nome, email ou nickname..."
            class="pl-10 bg-white"
          />
        </div>

        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm" @click="openCreateAdminModal">
          <span class="mr-2">🛡️</span> Novo Admin
        </Button>
      </div>
    </div>

    <Card class="shadow-sm border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-xs">
            <tr>
              <th class="px-6 py-4">Utilizador</th>
              <th class="px-6 py-4">Email</th>
              <th class="px-6 py-4 text-center">Tipo</th>
              <th class="px-6 py-4 text-center">Estado</th>
              <th class="px-6 py-4 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">

            <tr v-if="isLoading" class="animate-pulse">
              <td colspan="5" class="px-6 py-8 text-center text-slate-400">A carregar utilizadores...</td>
            </tr>

            <tr v-else-if="filteredUsers.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-slate-400">Nenhum utilizador encontrado.</td>
            </tr>

            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="h-9 w-9 rounded-full bg-slate-200 overflow-hidden border">
                    <img
                      :src="getAvatarUrl(user)"
                      class="h-full w-full object-cover"
                      @error="handleImageError"
                    />
                  </div>
                  <div>
                    <div class="font-bold text-slate-800">{{ user.name }}</div>
                    <div class="text-xs text-slate-500">@{{ user.nickname || '---' }}</div>
                  </div>
                </div>
              </td>

              <td class="px-6 py-4 text-slate-600">{{ user.email }}</td>

              <td class="px-6 py-4 text-center">
                <span
                  class="px-2 py-1 rounded-full text-xs font-bold border"
                  :class="user.type === 'A'
                    ? 'bg-purple-50 text-purple-700 border-purple-200'
                    : 'bg-blue-50 text-blue-700 border-blue-200'"
                >
                  {{ user.type === 'A' ? 'Admin' : 'Jogador' }}
                </span>
              </td>

              <td class="px-6 py-4 text-center">
                <span
                  class="px-2 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1"
                  :class="user.blocked
                    ? 'bg-red-50 text-red-700 border-red-200'
                    : 'bg-green-50 text-green-700 border-green-200'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="user.blocked ? 'bg-red-600' : 'bg-green-600'"></span>
                  {{ user.blocked ? 'Bloqueado' : 'Ativo' }}
                </span>
              </td>

              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                  <template v-if="user.id !== authStore.user?.id">
                    <Button
                      size="sm"
                      variant="outline"
                      @click="toggleBlockStatus(user)"
                      :class="user.blocked ? 'text-green-600 border-green-200' : 'text-orange-600 border-orange-200'"
                    >
                      {{ user.blocked ? '🔓 Desbloquear' : '🔒 Bloquear' }}
                    </Button>

                    <Button
                      size="sm"
                      variant="outline"
                      class="text-red-600 border-red-200"
                      @click="confirmDelete(user)"
                    >
                      🗑️ Remover
                    </Button>
                  </template>
                  <span v-else class="text-xs text-slate-400 italic px-2">(Eu)</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>

    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
      <Card class="w-full max-w-md bg-white shadow-xl p-6">
        <h3 class="text-xl font-bold mb-4 flex items-center gap-2">🛡️ Criar Administrador</h3>
        <form @submit.prevent="createAdmin" class="space-y-4">
          <Input v-model="newAdmin.name" required placeholder="Nome Completo" />
          <Input v-model="newAdmin.email" type="email" required placeholder="Email Institucional" />
          <Input v-model="newAdmin.password" type="password" required placeholder="Password (mín. 3 chars)" />
          <Input v-model="newAdmin.nickname" placeholder="Nickname" />

          <div v-if="createError" class="p-2 text-sm text-red-600 bg-red-50 rounded">{{ createError }}</div>

          <div class="flex justify-end gap-3 mt-6">
            <Button type="button" variant="ghost" @click="showCreateModal = false">Cancelar</Button>
            <Button type="submit" class="bg-indigo-600 text-white" :disabled="isCreating">
              {{ isCreating ? 'A processar...' : 'Criar Conta' }}
            </Button>
          </div>
        </form>
      </Card>
    </div>

    <ConfirmModal
      :is-open="showDeleteModal"
      title="Remover Utilizador?"
      :message="`Deseja remover ${userToDelete?.name}? Se houver histórico de jogos ou moedas, será efetuado um soft-delete para preservar os dados.`"
      confirm-text="Remover Conta"
      :is-danger="true"
      @confirm="deleteUser"
      @close="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAPIStore } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

// Inicialização das stores para comunicação com o backend e estado de autenticação (NF2/NF7)
const apiStore = useAPIStore()
const authStore = useAuthStore()

// Estados reativos para gestão da lista e carregamento
const users = ref([])
const isLoading = ref(true)
const searchQuery = ref('')

// Estados de controlo para modais e formulários (G5)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const userToDelete = ref(null)
const isCreating = ref(false)
const createError = ref('')
const newAdmin = ref({ name: '', email: '', password: '', nickname: '' })

// --- FETCH DATA ---
// Recupera todos os utilizadores da plataforma para visualização administrativa (G5)
const fetchUsers = async () => {
  isLoading.value = true
  try {
    const response = await apiStore.get('users')
    users.value = response.data.data || []
  } catch (error) {
    console.error("Erro ao carregar utilizadores", error)
  } finally {
    isLoading.value = false
  }
}

// --- FILTRO (G5) ---
// Filtra a lista localmente para melhorar a performance e responsividade da interface (NF8)
const filteredUsers = computed(() => {
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(u =>
    u.name.toLowerCase().includes(query) ||
    u.email.toLowerCase().includes(query) ||
    u.nickname?.toLowerCase().includes(query)
  )
})

// --- ACTIONS (G5/G1) ---

// Alterna o estado 'blocked' do utilizador no servidor
const toggleBlockStatus = async (user) => {
  try {
    const newStatus = !user.blocked
    await apiStore.patch(`users/${user.id}/block`)
    user.blocked = newStatus
  } catch (error) {
    alert("Falha ao alterar estado de bloqueio.")
  }
}

// Cria uma nova conta de administrador, restrita a administradores existentes
const createAdmin = async () => {
  isCreating.value = true
  createError.value = ''
  try {
    const response = await apiStore.post('admin/users', { ...newAdmin.value, type: 'A' })
    users.value.unshift(response.data.data)
    showCreateModal.value = false
  } catch (error) {
    createError.value = error.response?.data?.message || "Erro na criação."
  } finally {
    isCreating.value = false
  }
}

// Prepara o utilizador selecionado para remoção (G5)
const confirmDelete = (user) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

// Executa a remoção lógica (soft-delete) ou física do utilizador
const deleteUser = async () => {
  if (!userToDelete.value) return
  try {
    await apiStore.delete(`users/${userToDelete.value.id}`)
    users.value = users.value.filter(u => u.id !== userToDelete.value.id)
  } catch (error) {
    alert("Erro ao remover utilizador.")
  } finally {
    showDeleteModal.value = false
    userToDelete.value = null
  }
}

// --- HELPERS ---

// Constrói o URL da foto ou gera um avatar baseado no nome para melhor UX (NF5)
const getAvatarUrl = (user) => {
  if (user.photo_avatar_filename) {
    return `http://localhost:8000/storage/photos_avatars/${user.photo_avatar_filename}`
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random`
}

// Fallback caso o carregamento da imagem falhe (NF5)
const handleImageError = (e) => {
  e.target.src = 'https://ui-avatars.com/api/?name=User&background=ccc'
}

// Prepara o formulário para a criação de um novo administrador
const openCreateAdminModal = () => {
  newAdmin.value = { name: '', email: '', password: '', nickname: '' }
  createError.value = ''
  showCreateModal.value = true
}

// Ciclo de vida: Inicia a obtenção de dados ao montar o componente
onMounted(fetchUsers)
</script>
