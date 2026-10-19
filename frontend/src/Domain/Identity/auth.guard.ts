import type { Router } from 'vue-router'
import { useAuthStore } from './auth.store'
import { logSecurityEvent } from '@/utils/logger'

export const LOGIN_ROUTE = 'login'
export const HOME_AFTER_LOGIN = '/painel'

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

    if (to.meta.requiresAuth && !(await auth.ensureSession())) {
      logSecurityEvent('route.access_denied', { path: to.path })
      return { name: LOGIN_ROUTE, query: { redirect: to.fullPath } }
    }

    if (to.name === LOGIN_ROUTE && auth.hasValidAccess()) {
      return safeRedirect(to.query.redirect)
    }

    return true
  })
}
