import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layouts - Estrutura de organização visual para diferentes estados da SPA (NF1/NF4)
import AppLayout from '@/layouts/AppLayout.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'

// Vistas - Páginas principais da plataforma Bisca Game
import HomeView from '@/views/HomeView.vue'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import LobbyView from '@/views/LobbyView.vue'
import GameView from '@/views/GameView.vue'
import ProfileView from '@/views/ProfileView.vue'
import StoreView from '@/views/StoreView.vue'
import LeaderboardView from '@/views/LeaderboardView.vue'
import AdminView from '@/views/AdminView.vue'

// Componentes de Histórico (Reutilizáveis) - Para visualização de dados pessoais e auditoria (G4/G5)
import GameHistory from '@/components/history/GameHistory.vue'
import TransactionLog from '@/components/history/TransactionLog.vue'

const routes = [
  // Grupo 1: Autenticação (GuestLayout) - Acessível apenas a utilizadores não autenticados (G1)
  {
    path: '/auth',
    component: GuestLayout,
    children: [
      {
        path: 'login',
        name: 'login',
        component: LoginView,
        meta: { title: 'Entrar', guestOnly: true },
      },
      {
        path: 'register',
        name: 'register',
        component: RegisterView,
        meta: { title: 'Registar', guestOnly: true },
      },
    ],
  },

  // Grupo 2: Aplicação (AppLayout) - Estrutura principal com NavBar e Footer (NF4)
  {
    path: '/',
    component: AppLayout,
    children: [
      // Início e Leaderboards: Visíveis para todos, incluindo anónimos (G4)
      { path: '', name: 'home', component: HomeView, meta: { title: 'Início' } },
      {
        path: 'leaderboard',
        name: 'leaderboard',
        component: LeaderboardView,
        meta: { title: 'Top Jogadores' },
      },
      // Mesa de Jogo: Suporta modos single-player (anónimos) e multiplayer (registados) (G3)
      { path: 'game', name: 'game', component: GameView, meta: { title: 'Mesa de Jogo' } },

      // Rotas Protegidas - Jogador Registado (G1)
      {
        path: 'lobby',
        name: 'lobby',
        component: LobbyView,
        meta: { title: 'Lobby', requiresAuth: true },
      },
      {
        path: 'profile',
        name: 'profile',
        component: ProfileView,
        meta: { title: 'Perfil', requiresAuth: true },
      },
      {
        path: 'store',
        name: 'store',
        component: StoreView,
        meta: { title: 'Loja', requiresAuth: true },
      },

      // G4: Histórico Pessoal - Acesso apenas aos próprios registos
      {
        path: 'history/games',
        name: 'history-games',
        component: GameHistory,
        meta: { title: 'Meus Jogos', requiresAuth: true },
      },
      {
        path: 'history/wallet',
        name: 'history-wallet',
        component: TransactionLog,
        meta: { title: 'Movimentos', requiresAuth: true },
      },

      // G5: Administração - Acesso estrito a administradores para gestão e auditoria
      {
        path: 'admin',
        name: 'admin',
        component: AdminView,
        meta: { title: 'Painel Admin', requiresAuth: true, requiresAdmin: true },
      },
      {
        path: 'admin/history/games',
        name: 'admin-history-games',
        component: GameHistory,
        props: { isAdminMode: true }, // Reutilização lógica do componente para Auditoria Global
        meta: { title: 'Auditoria de Jogos', requiresAuth: true, requiresAdmin: true },
      },
      {
        path: 'admin/history/wallet',
        name: 'admin-history-wallet',
        component: TransactionLog,
        props: { isAdminMode: true }, // Auditoria Financeira Global
        meta: { title: 'Auditoria Financeira', requiresAuth: true, requiresAdmin: true },
      },
    ],
  },

  // Redirecionamentos de conveniência para melhorar a usabilidade (NF5)
  { path: '/login', redirect: { name: 'login' } },
  { path: '/register', redirect: { name: 'register' } },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: { name: 'home' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

/**
 * Navigation Guards - Implementação de Segurança (NF7) e UX (NF5)
 */
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // SEO: Atualização dinâmica do título do documento no browser
  document.title = to.meta.title ? `${to.meta.title} | Bisca DAD` : 'Bisca DAD'

  // Reidratação de Sessão (NF8): Garante integridade do estado em refrescos de página
  if (!authStore.user && localStorage.getItem('token')) {
    try {
      await authStore.checkAuth()
    } catch (e) {
      authStore.logout() // Limpa o estado se o token for inválido ou expirado
    }
  }

  const isAuthenticated = !!authStore.user
  const isAdmin = authStore.user?.type === 'A' // Tipo 'A' = Administrador

  // 1. Redireciona utilizadores já logados que tentam aceder a Login/Registo (NF5)
  if (isAuthenticated && to.meta.guestOnly) {
    return next({ name: 'home' })
  }

  // 2. Bloqueia acesso a áreas protegidas (G1/G3) para visitantes anónimos
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // 3. Bloqueio de áreas administrativas para jogadores comuns (G5)
  if (to.meta.requiresAdmin && !isAdmin) {
    return next({ name: 'home' })
  }

  next() // Permite a navegação se todas as validações de segurança passarem
})

export default router
