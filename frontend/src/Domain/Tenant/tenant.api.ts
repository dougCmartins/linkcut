import { http } from '@/http/client'

/** The signed-in barber's barbershop. Its slug is the public address (/:slug). */
export interface CurrentTenant {
  id: number
  name: string
  slug: string
}

export async function fetchCurrentTenant(): Promise<CurrentTenant> {
  const { data } = await http<CurrentTenant>('/tenant')
  return data
}
