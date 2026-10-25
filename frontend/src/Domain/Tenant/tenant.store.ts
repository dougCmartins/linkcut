import { defineStore } from 'pinia'
import { ref } from 'vue'
import { completeOnboarding, fetchCurrentTenant, type CurrentTenant } from './tenant.api'

/** The page this device last signed in from: where "Sair", an expired session and the installed app go back to. */
const LAST_SLUG_KEY = 'linkcut.last-slug'

export function lastBarberSlug(): string | null {
  try {
    return localStorage.getItem(LAST_SLUG_KEY)
  } catch {
    return null
  }
}

function rememberSlug(slug: string | null): void {
  try {
    if (slug) localStorage.setItem(LAST_SLUG_KEY, slug)
    else localStorage.removeItem(LAST_SLUG_KEY)
  } catch {
    // Storage blocked: "Encontrar minha página" (/entrar) still works.
  }
}

/** The signed-in barber's barbershop (slug, wizard done or not). Loaded once per session. */
export const useTenantStore = defineStore('tenant', () => {
  const tenant = ref<CurrentTenant | null>(null)
  let loading: Promise<CurrentTenant | null> | null = null

  /** Null when the API can't be reached (callers then let the barber through). */
  function ensureLoaded(): Promise<CurrentTenant | null> {
    if (tenant.value) return Promise.resolve(tenant.value)
    loading ??= fetchCurrentTenant()
      .then((loaded) => {
        rememberSlug(loaded.slug)
        return (tenant.value = loaded)
      })
      .catch(() => null)
      .finally(() => (loading = null))
    return loading
  }

  async function finishOnboarding(): Promise<void> {
    tenant.value = await completeOnboarding()
  }

  function clear(): void {
    tenant.value = null
  }

  /** The barbershop no longer exists (account deleted): this device stops pointing at its page. */
  function forgetPage(): void {
    clear()
    rememberSlug(null)
  }

  return { tenant, ensureLoaded, finishOnboarding, clear, forgetPage }
})
