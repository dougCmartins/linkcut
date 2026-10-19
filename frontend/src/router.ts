import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    /** Client-facing page: shows the visitor light/dark toggle. */
    isPublic?: boolean
    openBooking?: boolean
    /** Barber-only page: needs a session (see Domain/Identity/auth.guard.ts). */
    requiresAuth?: boolean
  }
}

export const routes: RouteRecordRaw[] = [
  { path: '/', name: 'home', component: () => import('@/Home/HomeView.vue') },
  { path: '/entrar', name: 'login', component: () => import('@/Domain/Identity/LoginView.vue') },
  {
    path: '/painel',
    name: 'dashboard',
    component: () => import('@/Dashboard/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel/historico',
    name: 'history',
    component: () => import('@/History/HistoryView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel/despesas',
    name: 'expenses',
    component: () => import('@/Expenses/ExpensesView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel/configuracoes',
    name: 'settings',
    component: () => import('@/Domain/Settings/SettingsView.vue'),
    meta: { requiresAuth: true },
  },
  // Public pages of each barbershop, by slug. Kept after the fixed paths above.
  { path: '/:slug', name: 'bio-link', component: () => import('@/BioLink/BioLinkView.vue'), meta: { isPublic: true } },
  {
    path: '/:slug/portfolio',
    name: 'portfolio',
    component: () => import('@/Portfolio/PortfolioView.vue'),
    meta: { isPublic: true },
  },
  // Opens the booking modal on top of the bio link page.
  {
    path: '/:slug/agendar',
    name: 'booking',
    component: () => import('@/BioLink/BioLinkView.vue'),
    meta: { isPublic: true, openBooking: true },
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})
