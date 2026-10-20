import type { Router } from 'vue-router'
import { useClientStore } from './client.store'

export const CLIENT_LOGIN_ROUTE = 'client-login'
export const CLIENT_HOME = '/conta'

/** Only same-app paths are accepted as a redirect target (no open redirects). */
export function safeClientRedirect(target: unknown): string {
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//') ? target : CLIENT_HOME
}

/** Routes with `meta.requiresClient` need the client's session (independent of the barber's). */
export function installClientGuard(router: Router): void {
  router.beforeEach(async (to) => {
    if (!to.meta.requiresClient) return true
    const client = useClientStore()
    client.onSessionExpired(() => {
      const current = router.currentRoute.value
      if (current.meta.requiresClient)
        void router.replace({
          name: CLIENT_LOGIN_ROUTE,
          query: { redirect: current.fullPath },
        })
    })
    return (
      (await client.ensureSession()) || {
        name: CLIENT_LOGIN_ROUTE,
        query: { redirect: to.fullPath },
      }
    )
  })
}
