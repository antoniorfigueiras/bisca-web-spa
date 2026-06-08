import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * NF5: Sistema de notificações global para feedback imediato ao utilizador.
 * Garante que o utilizador recebe avisos sobre ganhos de moedas (G2), eventos de jogo (G3)
 * e comunicações administrativas em tempo real (G5).
 */
export const useToastStore = defineStore('toast', () => {

  // Lista reativa de notificações ativas que serão renderizadas pelo GlobalToaster.vue (NF4)
  const toasts = ref([])

  // Contador interno para garantir chaves (keys) únicas no TransitionGroup (NF5)
  let idCounter = 0

  // --- AÇÕES PRINCIPAIS ---

  /**
   * Adiciona uma notificação à pilha com auto-remoção.
   * @param {Object} options - { title, message, type, duration }
   */
  const add = ({ title, message, type = 'info', duration = 5000 }) => {
    const id = idCounter++

    const toast = {
      id,
      title,
      message,
      type, // 'success', 'error', 'info', 'warning'
      duration
    }

    // Adiciona a notificação para ser processada pela UI (NF1)
    toasts.value.push(toast)

    /**
     * NF5: Mecanismo de auto-limpeza.
     * Evita a acumulação de elementos na interface, mantendo a área de jogo desimpedida (G3).
     */
    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }
  }

  /**
   * Remove uma notificação específica pelo ID.
   * Invocado automaticamente pelo timer ou manualmente pelo botão "Fechar" no Toast.
   */
  const remove = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  // --- ATALHOS SEMÂNTICOS (SHORTHANDS) ---

  /**
   * G2/G3: Feedback positivo.
   * Ex: "Compra de moedas efetuada com sucesso!" ou "Ganhou a vaza!" .
   */
  const showSuccess = (message, title = 'Sucesso') => {
    add({ title, message, type: 'success' })
  }

  /**
   * G3/G2: Notificação de erro ou validação falhada.
   * Ex: "Cartas insuficientes para jogar" ou "Falha no pagamento".
   */
  const showError = (message, title = 'Erro') => {
    add({ title, message, type: 'error' })
  }

  /**
   * G3/G5: Mensagens informativas do sistema ou de rede.
   * Ex: "Nova proposta de aposta recebida".
   */
  const showInfo = (message, title = 'Informação') => {
    add({ title, message, type: 'info' })
  }

  /**
   * G5: Alertas de sistema com maior tempo de exposição.
   * Ex: "O servidor irá reiniciar para manutenção" ou "Conta bloqueada pelo administrador".
   */
  const showWarning = (message, title = 'Aviso') => {
    add({ title, message, type: 'warning', duration: 7000 })
  }

  return {
    toasts,
    add,
    remove,
    showSuccess,
    showError,
    showInfo,
    showWarning
  }
})
