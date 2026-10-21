import { defineStore } from 'pinia'
import { ref } from 'vue'
import { completeOnboarding, fetchCurrentTenant, type CurrentTenant } from './tenant.api'

/** The signed-in barber's barbershop (slug, wizard done or not). Loaded once per session. */
export const useTenantStore = defineStore('tenant', () => {
  const tenant = ref<CurrentTenant | null>(null)
  let loading: Promise<CurrentTenant | null> | null = null

  /** Null when the API can't be reached (callers then let the barber through). */
  function ensureLoaded(): Promise<CurrentTenant | null> {
    if (tenant.value) return Promise.resolve(tenant.value)
    loading ??= fetchCurrentTenant()
      .then((loaded) => (tenant.value = loaded))
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

  return { tenant, ensureLoaded, finishOnboarding, clear }
})
