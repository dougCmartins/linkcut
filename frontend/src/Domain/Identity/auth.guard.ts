import type { RouteLocationRaw, Router } from 'vue-router'
import { useAuthStore } from './auth.store'
import { lastBarberSlug, useTenantStore } from '@/Domain/Tenant/tenant.store'
import { logSecurityEvent } from '@/utils/logger'

export const LOGIN_ROUTE = 'login'
export const BARBER_LOGIN_ROUTE = 'barber-login'

/** Where to sign in: the page this device used last (/<slug>/entrar), else "Encontrar minha página" (/entrar). */
export function loginLocation(redirect?: string, slug: string | null = lastBarberSlug()): RouteLocationRaw {
  const query = redirect ? { redirect } : {}
  return slug ? { name: BARBER_LOGIN_ROUTE, params: { slug }, query } : { name: LOGIN_ROUTE, query }
}
export const HOME_AFTER_LOGIN = '/painel'
export const ONBOARDING_ROUTE = 'onboarding'

/** Only same-app paths are accepted as a redirect target (no open redirects). */
export function safeRedirect(target: unknown): string {
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//') ? target : HOME_AFTER_LOGIN
}

/**
 * Central access rule: routes with `meta.requiresAuth` need a session.
 * Without one, the barber goes to the login and comes back to the page they asked for.
 */
export function installAuthGuard(router: Router): void {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()
    // The session can also end in the middle of a page (refresh refused by the API).
    auth.onSessionExpired(() => {
      const current = router.currentRoute.value
      if (current.meta.requiresAuth) void router.replace(loginLocation(current.fullPath))
    })

    if (to.meta.requiresAuth && !(await auth.ensureSession())) {
      logSecurityEvent('route.access_denied', { path: to.path })
      return loginLocation(to.fullPath)
    }

    // A new barbershop goes through the wizard first (WhatsApp, address, services); then never again.
    if (to.meta.requiresAuth) {
      const tenant = await useTenantStore().ensureLoaded()
      if (tenant && !tenant.onboarded && to.name !== ONBOARDING_ROUTE) return { name: ONBOARDING_ROUTE }
      if (tenant?.onboarded && to.name === ONBOARDING_ROUTE) return HOME_AFTER_LOGIN
    }

    // Signed in already: the entrance (/, /cadastrar, /entrar) goes on to the dashboard.
    if (to.meta.authMode && auth.hasValidAccess()) {
      return safeRedirect(to.query.redirect)
    }

    return true
  })
}
