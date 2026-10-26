import type { LocationQueryRaw, RouteLocationRaw, Router } from 'vue-router'
import { lastVisitedSlug } from '@/Domain/Settings/publicPage.store'
import { useClientStore } from './client.store'

/** /conta/entrar: only when this device never opened a barbershop page; otherwise it sends on to /<slug>/login. */
export const CLIENT_LOGIN_ROUTE = 'client-login'
/** /<slug>/login: the client's sign-in with the barbershop's theme. */
export const BARBERSHOP_CLIENT_LOGIN_ROUTE = 'barbershop-client-login'
export const CLIENT_HOME = '/conta'

/** Where the client signs in: the last barbershop page opened here (/<slug>/login), else /conta/entrar. */
export function clientLoginLocation(query: LocationQueryRaw = {}, slug: string | null = lastVisitedSlug()): RouteLocationRaw {
  return slug ? { name: BARBERSHOP_CLIENT_LOGIN_ROUTE, params: { slug }, query } : { name: CLIENT_LOGIN_ROUTE, query }
}

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
      if (current.meta.requiresClient) void router.replace(clientLoginLocation({ redirect: current.fullPath }))
    })
    return (await client.ensureSession()) || clientLoginLocation({ redirect: to.fullPath })
  })
}
