import type { LocationQueryRaw, RouteLocationRaw, RouteParamsRaw, Router } from 'vue-router'
import { lastVisitedSlug } from '@/Domain/Settings/publicPage.store'
import { useClientStore } from './client.store'

/** /conta/entrar: only when this device never opened a barbershop page; otherwise it sends on to /<slug>/login. */
export const CLIENT_LOGIN_ROUTE = 'client-login'
/** /<slug>/login: the client's sign-in with the barbershop's theme. */
export const BARBERSHOP_CLIENT_LOGIN_ROUTE = 'barbershop-client-login'
/** /<slug>/account…: the client area inside a barbershop. */
export const CLIENT_APPOINTMENTS_ROUTE = 'client-appointments'
export const CLIENT_BOOK_ROUTE = 'client-book'
export const CLIENT_PROFILE_ROUTE = 'client-profile'
export const CLIENT_REVIEW_ROUTE = 'client-review'
/** /account: the barbershops where the client booked (no barbershop chosen yet). */
export const CLIENT_BARBERSHOPS_ROUTE = 'client-barbershops'
export const CLIENT_HOME = '/account'

/** The client area of `slug` (or the list of barbershops, when there is none). */
export function clientAccountLocation(
  slug: string | null,
  name: string = CLIENT_APPOINTMENTS_ROUTE,
  params: RouteParamsRaw = {},
): RouteLocationRaw {
  return slug ? { name, params: { ...params, slug } } : { name: CLIENT_BARBERSHOPS_ROUTE }
}

/** Where the client signs in: the barbershop in the address or the last one opened here (/<slug>/login), else /conta/entrar. */
export function clientLoginLocation(query: LocationQueryRaw = {}, slug: string | null = lastVisitedSlug()): RouteLocationRaw {
  return slug ? { name: BARBERSHOP_CLIENT_LOGIN_ROUTE, params: { slug }, query } : { name: CLIENT_LOGIN_ROUTE, query }
}

/** Only same-app paths are accepted as a redirect target (no open redirects). */
export function safeClientRedirect(target: unknown, fallback: string = CLIENT_HOME): string {
  return typeof target === 'string' && target.startsWith('/') && !target.startsWith('//') ? target : fallback
}

const slugOf = (params: Record<string, unknown>): string | null => (typeof params.slug === 'string' ? params.slug : null)

/** Routes with `meta.requiresClient` need the client's session (independent of the barber's). */
export function installClientGuard(router: Router): void {
  router.beforeEach(async (to) => {
    if (!to.meta.requiresClient) return true
    const client = useClientStore()
    client.onSessionExpired(() => {
      const current = router.currentRoute.value
      if (current.meta.requiresClient)
        void router.replace(clientLoginLocation({ redirect: current.fullPath }, slugOf(current.params) ?? lastVisitedSlug()))
    })
    return (await client.ensureSession()) || clientLoginLocation({ redirect: to.fullPath }, slugOf(to.params) ?? lastVisitedSlug())
  })
}
