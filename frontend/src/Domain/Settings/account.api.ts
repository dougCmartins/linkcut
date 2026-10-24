import { http } from '@/http/client'

/** The barber's own barbershop account (backend: Orchestrator/BarbershopAccount). LGPD: export and deletion. */
export async function exportBarbershopData(): Promise<unknown> {
  const { data } = await http<unknown>('/account/export')
  return data
}

/** Everything of the barbershop is deleted. A wrong password or link answers VALIDATION_FAILED on that field. */
export async function deleteBarbershop(input: { password: string; slugConfirmation: string }): Promise<void> {
  await http<void>('/account', { method: 'DELETE', body: { password: input.password, slug_confirmation: input.slugConfirmation } })
}
