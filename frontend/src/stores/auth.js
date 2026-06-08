import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import socketService from '@/services/socket'

/**
 * G1: Gestão de autenticação, perfil e sessão.
 * G2: Monitorização do saldo de moedas em tempo real.
 */
export const useAuthStore = defineStore('auth', () => {

  const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

  // --- ESTADO (NF8: Persistência local para hidratação da SPA) ---
  const user = ref(localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null)
  const token = ref(localStorage.getItem('token') || null)

  // --- GETTERS ---
  const isLoggedIn = computed(() => !!token.value)
  // Administrador possui tipo 'A', Jogador possui tipo 'P'
  const isAdmin = computed(() => user.value?.type === 'A')

  // G2: Apenas jogadores têm saldo de moedas disponível para apostas
  const coins = computed(() => user.value?.coins_balance ?? 0)

  // --- AÇÕES ---

  /**
   * G1: Efetua a autenticação e inicializa serviços dependentes.
   * Estabelece a ligação WebSocket para permitir jogos multiplayer (NF9).
   */
  async function login(credentials) {
    try {
      const response = await axios.post(`${API_URL}/login`, credentials)

      const accessToken = response.data.access_token
      const userData = response.data.user

      // Atualizar Estado e Persistir (NF7: Segurança através de Bearer Token)
      token.value = accessToken
      user.value = userData
      localStorage.setItem('token', accessToken)
      localStorage.setItem('user', JSON.stringify(userData))

      // Configurar Headers do Axios e inicializar Sockets (NF2/NF9)
      axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`
      socketService.init()

      return true
    } catch (error) {
      console.error('Erro no Login:', error)
      throw error
    }
  }

  /**
   * G1: Registo de novo utilizador.
   * Corrige o endpoint para /register conforme a estrutura da API Restful.
   */
  async function register(credentials) {
    try {
      // NF2: Comunicação com o servidor para criação de conta.
      const response = await axios.post(`${API_URL}/register`, credentials)

      // Configuração automática da sessão caso a API realize auto-login após o registo
      if (response.data.access_token) {
        const accessToken = response.data.access_token
        const userData = response.data.user

        token.value = accessToken
        user.value = userData
        localStorage.setItem('token', accessToken)
        localStorage.setItem('user', JSON.stringify(userData))

        axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`
        socketService.init()
      }

      return response.data
    } catch (error) {
      console.error('Erro no Registo:', error)
      throw error
    }
  }

 /**
  * NF7: Limpeza atómica da sessão para garantir a privacidade dos dados.
  * Encerra comunicações ativas antes de limpar o estado local (NF9).
  */
function clearSession() {
  // 1. Terminar ligação WebSocket primeiro para evitar erros de autenticação no socket (NF9)
  if (socketService) {
    socketService.disconnect()
  }

  // 2. Limpar os headers globais de autorização (NF2)
  delete axios.defaults.headers.common['Authorization']

  // 3. Reset do Estado Reativo da Pinia
  token.value = null
  user.value = null

  // 4. Limpar o armazenamento local persistido (NF8)
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}

/**
 * G1: Terminar sessão no servidor e limpar localmente.
 */
async function logout() {
  try {
    if (token.value) {
      await axios.post(`${API_URL}/logout`, {}, {
        headers: { Authorization: `Bearer ${token.value}` }
      })
    }
  } catch (error) {
    console.warn('Token já era inválido ou sessão expirada no servidor.')
  } finally {
    clearSession()
  }
}

  /**
   * G1/G2: Validação da sessão e atualização do perfil.
   * Chamado na inicialização para garantir que os dados (moedas/avatar) estão atuais.
   */
  async function checkAuth() {
    if (!token.value) return

    try {
      // NF2: Obtém os dados do utilizador autenticado (/users/me)
      const response = await axios.get(`${API_URL}/users/me`, {
        headers: { Authorization: `Bearer ${token.value}` }
      })

      user.value = response.data.data || response.data
      localStorage.setItem('user', JSON.stringify(user.value))

      // NF8: Restabelece ligação ao Socket após recarregamento da página
      if (isLoggedIn.value) {
        socketService.init()
      }

    } catch (error) {
      // NF7: Se o token for inválido (401), força o logout por segurança
      if (error.response?.status === 401) {
        await logout()
      }
    }
  }

  /**
   * G2: Atualização pontual do saldo de moedas ou foto de perfil.
   * Invocado após transações financeiras ou finalização de partidas.
   */
  async function refreshUser() {
    return checkAuth()
  }

  return {
    user,
    token,
    isLoggedIn,
    isAdmin,
    coins,
    login,
    register,
    logout,
    checkAuth,
    refreshUser,
    clearSession
  }
})
