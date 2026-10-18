import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

export const routes: RouteRecordRaw[] = [
  { path: '/', name: 'bio-link', component: () => import('@/BioLink/BioLinkView.vue') },
  { path: '/portfolio', name: 'portfolio', component: () => import('@/Portfolio/PortfolioView.vue') },
  // Opens the booking modal on top of the bio link page.
  { path: '/agendar', name: 'booking', component: () => import('@/BioLink/BioLinkView.vue'), meta: { openBooking: true } },
  { path: '/painel', name: 'dashboard', component: () => import('@/Dashboard/DashboardView.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})
