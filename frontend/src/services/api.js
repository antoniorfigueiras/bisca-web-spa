import { defineStore } from 'pinia'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

/**
 * ]NF2: Implementação de cliente para o servidor RESTful API.
 * Centraliza a configuração do Axios para garantir consistência em todos os pedidos e seguir as boas práticas (NF3).
 */
export const useAPIStore = defineStore('api', {
  state: () => ({
    /**
     * NF8: Base URL configurada para o servidor da escola ou ambiente local.
     * A aplicação deve ser acedida via browser num ambiente de produção simulado.
     */
    axiosInstance: axios.create({
      baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      withCredentials: true
    }),
    isLoading: false,
  }),

  actions: {
    /**
     * NF7: Configuração de Intercetores de Segurança.
     * Injeta automaticamente o Bearer Token em todos os pedidos para cumprir as regras de autorização.
     * Protege os dados e a privacidade do utilizador seguindo as melhores práticas.
     */
    setupInterceptors() {
      // Pedidos: Injetar Token de autenticação se este existir na store
      this.axiosInstance.interceptors.request.use((config) => {
        const authStore = useAuthStore()
        if (authStore.token) {
          config.headers.Authorization = `Bearer ${authStore.token}`
        }
        return config
      })

      /**
       * Respostas: Lida com a expiração de sessão ou erros de autenticação (401).
       * Se a sessão expirar, limpa os dados locais para manter a segurança do sistema.
       */
      this.axiosInstance.interceptors.response.use(
        (response) => response,
        (error) => {
          const authStore = useAuthStore()

          if (error.response?.status === 401) {
            // Se o servidor rejeitar o token, força o encerramento da sessão local (G1)
            if (authStore.token) {
              authStore.clearSession()
            }
          }
          return Promise.reject(error)
        },
      )
    },

    // --- MÉTODOS HTTP WRAPPERS PARA COMUNICAÇÃO COM A API RESTFUL ---

    /**
     * Método GET: Utilizado para obter dados como histórico de jogos, transações ou leaderboards.
     */
    async get(endpoint, config = {}) {
      this.isLoading = true
      try {
        return await this.axiosInstance.get(endpoint, config)
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Método POST: Utilizado para criar novos recursos como registo de utilizadores ou transações de moedas.
     */
    async post(endpoint, data, config = {}) {
      this.isLoading = true
      try {
        return await this.axiosInstance.post(endpoint, data, config)
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Método PUT: Utilizado para atualizações completas de perfil.
     */
    async put(endpoint, data, config = {}) {
      this.isLoading = true
      try {
        return await this.axiosInstance.put(endpoint, data, config)
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Método PATCH: Utilizado para atualizações parciais, como bloquear/unbloquear jogadores (G5).
     */
    async patch(endpoint, data, config = {}) {
      this.isLoading = true
      try {
        return await this.axiosInstance.patch(endpoint, data, config)
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Método DELETE: Utilizado para a remoção de contas, garantindo o soft-delete se houver histórico.
     */
    async delete(endpoint, config = {}) {
      this.isLoading = true
      try {
        return await this.axiosInstance.delete(endpoint, config)
      } finally {
        this.isLoading = false
      }
    },
  },
})
