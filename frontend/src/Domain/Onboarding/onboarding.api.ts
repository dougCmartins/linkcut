import { http } from '@/http/client'
import { toSession, type SessionResponse } from '@/Domain/Identity/auth.api'
import type { Session } from '@/Domain/Identity/auth.model'

/** Open sign-up of barbers (backend: Orchestrator/Onboarding). snake_case only here. */

export interface SignUpInput {
  name: string
  email: string
  password: string
  code: string
  barbershopName: string
  slug: string
}

export interface SlugAvailability {
  available: boolean
  /** A free link close to the one asked (the same one when free). */
  suggestion: string
}

/** Same answer for every e-mail; resolves with when a new code can be asked (epoch ms). */
export async function sendSignUpCode(email: string, now = Date.now()): Promise<number> {
  const { data } = await http<{ resend_after: number }>('/sign-up/code', { method: 'POST', auth: false, body: { email } })
  return now + data.resend_after * 1000
}

export async function checkSlug(slug: string): Promise<SlugAvailability> {
  const { data } = await http<SlugAvailability>(`/sign-up/slug?slug=${encodeURIComponent(slug)}`, { auth: false })
  return { available: data.available, suggestion: data.suggestion }
}

/** Creates the barbershop and its owner; answers with the session (the e-mail was just confirmed). */
export async function signUp(input: SignUpInput): Promise<Session> {
  const { data } = await http<SessionResponse>('/sign-up', {
    method: 'POST',
    auth: false,
    body: {
      name: input.name,
      email: input.email,
      password: input.password,
      code: input.code,
      barbershop_name: input.barbershopName,
      slug: input.slug,
    },
  })
  return toSession(data)
}
