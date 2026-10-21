import { http } from '@/http/client'

/** The signed-in barber's barbershop. Its slug is the public address (/:slug). */
export interface CurrentTenant {
  id: number
  name: string
  slug: string
  /** Finished the first-run wizard (/painel/boas-vindas). */
  onboarded: boolean
}

export async function fetchCurrentTenant(): Promise<CurrentTenant> {
  const { data } = await http<CurrentTenant>('/tenant')
  return data
}

/** End of the wizard: the panel stops sending the barber there. */
export async function completeOnboarding(): Promise<CurrentTenant> {
  const { data } = await http<CurrentTenant>('/tenant/onboarding/complete', { method: 'POST' })
  return data
}
