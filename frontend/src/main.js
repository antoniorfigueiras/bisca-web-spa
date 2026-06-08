import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAPIStore } from '@/services/api'

/**
 * NF4: Estilos globais e Design System.
 * Importação do ficheiro principal que contém as diretivas do Tailwind CSS
 * e as variáveis de tema do Shadcn/UI.
 */
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()

/**
 * NF1: Gestão de Estado Global.
 * Pinia é inicializada antes de qualquer componente ou serviço para garantir que
 * as stores estão disponíveis para a configuração da infraestrutura de rede.
 */
app.use(pinia)

/**
 * NF7: Inicialização da Camada de Segurança.
 * Os intercetores do Axios são configurados imediatamente após a ativação da Pinia.
 * Este passo é crítico para garantir que o Bearer Token de autenticação seja
 * injetado em todos os pedidos à API RESTful (NF2).
 */
const apiStore = useAPIStore()
apiStore.setupInterceptors()

/**
 * NF5: Sistema de Navegação.
 * O Router é registado por último para que os Navigation Guards possam injetar
 * a lógica de proteção de rotas (requiresAuth, requiresAdmin) com acesso pleno
 * ao estado da aplicação.
 */
app.use(router)

/**
 * NF1: Montagem da SPA (Single-Page Application).
 * A aplicação é montada no elemento DOM '#app' definido no ficheiro index.html.
 */
app.mount('#app')
