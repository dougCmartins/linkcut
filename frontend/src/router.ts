import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { BARBERSHOP_CLIENT_LOGIN_ROUTE } from '@/Domain/Client/client.guard'
import { lastVisitedSlug } from '@/Domain/Settings/publicPage.store'

declare module 'vue-router' {
  interface RouteMeta {
    /** Client-facing page: shows the visitor light/dark toggle. */
    isPublic?: boolean
    openBooking?: boolean
    /** Barber-only page: needs a session (see Domain/Identity/auth.guard.ts). */
    requiresAuth?: boolean
    /** Client-area page: needs the client's session (see Domain/Client/client.guard.ts). */
    requiresClient?: boolean
    /** linkcut's entrance (/, /cadastrar, /entrar): one page, the mode picks the form and the photo. */
    authMode?: 'sign-up' | 'login'
    /** The client's entrance (/<slug>/login, /conta/entrar, /conta/finalizar): same idea. */
    clientAuthMode?: 'login' | 'register'
  }
}

const BarberAuthView = () => import('@/Home/BarberAuthView.vue')
const ClientAuthView = () => import('@/Domain/Client/ClientAuthView.vue')

export const routes: RouteRecordRaw[] = [
  // One component for the three: switching keeps the page and only swaps the form and the photo.
  { path: '/', name: 'home', component: BarberAuthView, meta: { authMode: 'sign-up' } },
  { path: '/cadastrar', name: 'sign-up', component: BarberAuthView, meta: { authMode: 'sign-up' } },
  { path: '/entrar', name: 'login', component: BarberAuthView, meta: { authMode: 'login' } },
  {
    path: '/painel/boas-vindas',
    name: 'onboarding',
    component: () => import('@/Domain/Onboarding/OnboardingView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel',
    name: 'dashboard',
    component: () => import('@/Dashboard/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel/historico',
    name: 'history',
    component: () => import('@/Domain/Finance/History/HistoryView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel/despesas',
    name: 'expenses',
    component: () => import('@/Domain/Finance/Expenses/ExpensesView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/painel/configuracoes',
    name: 'settings',
    component: () => import('@/Domain/Settings/SettingsView.vue'),
    meta: { requiresAuth: true },
  },
  // Client area (one account for every barbershop where the client booked).
  {
    path: '/conta',
    name: 'client-appointments',
    component: () => import('@/Domain/Client/ClientAppointmentsView.vue'),
    meta: { requiresClient: true },
  },
  {
    path: '/conta/barbearias',
    name: 'client-barbershops',
    component: () => import('@/Domain/Client/ClientBarbershopsView.vue'),
    meta: { requiresClient: true },
  },
  {
    path: '/conta/perfil',
    name: 'client-profile',
    component: () => import('@/Domain/Client/ClientProfileView.vue'),
    meta: { requiresClient: true },
  },
  // Kept for links that have no barbershop (e-mails, Google's return): sends on to the last /<slug>/login.
  {
    path: '/conta/entrar',
    name: 'client-login',
    component: ClientAuthView,
    meta: { clientAuthMode: 'login' },
    beforeEnter: (to) => {
      const slug = lastVisitedSlug()
      return slug ? { name: BARBERSHOP_CLIENT_LOGIN_ROUTE, params: { slug }, query: to.query, replace: true } : true
    },
  },
  { path: '/conta/finalizar', name: 'client-register', component: ClientAuthView, meta: { clientAuthMode: 'register' } },
  {
    path: '/conta/entrar/google',
    name: 'client-google',
    component: () => import('@/Domain/Client/ClientGoogleCallbackView.vue'),
  },
  {
    path: '/conta/lembretes-desativados',
    name: 'client-opt-out',
    component: () => import('@/Domain/Client/ClientOptOutView.vue'),
  },
  { path: '/privacidade', name: 'privacy', component: () => import('@/Legal/LegalDocumentView.vue'), props: { document: 'privacy' } },
  { path: '/termos', name: 'terms', component: () => import('@/Legal/LegalDocumentView.vue'), props: { document: 'terms' } },
  // Public pages of each barbershop, by slug. Kept after the fixed paths above.
  { path: '/:slug', name: 'bio-link', component: () => import('@/BioLink/BioLinkView.vue'), meta: { isPublic: true } },
  {
    path: '/:slug/portfolio',
    name: 'portfolio',
    component: () => import('@/Portfolio/PortfolioView.vue'),
    meta: { isPublic: true },
  },
  // The client's sign-in, with the barbershop's theme. The client area itself stays at /conta.
  {
    path: '/:slug/login',
    name: BARBERSHOP_CLIENT_LOGIN_ROUTE,
    component: ClientAuthView,
    meta: { isPublic: true, clientAuthMode: 'login' },
  },
  // The barber's sign-in on the barbershop's own page, with its theme (only its barbers get in).
  {
    path: '/:slug/panel/login',
    name: 'barber-login',
    component: () => import('@/Home/BarberPageLoginView.vue'),
    meta: { isPublic: true, authMode: 'login' },
  },
  // Old address of the barber's sign-in, still in e-mails already sent.
  { path: '/:slug/entrar', redirect: (to) => ({ name: 'barber-login', params: to.params, query: to.query }) },
  // Opens the booking modal on top of the portfolio, as its own history entry.
  {
    path: '/:slug/portfolio/agendar',
    name: 'portfolio-booking',
    component: () => import('@/Portfolio/PortfolioView.vue'),
    meta: { isPublic: true, openBooking: true },
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
