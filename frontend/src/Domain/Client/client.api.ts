import { http } from '@/http/client'
import type { AppointmentStatus } from '@/types/appointmentStatus'
import type { ClientAppointment, ClientProfile, ClientSession, CodePurpose, CodeSent } from './client.model'

/** Client area API (backend: src/Domain/Client + Orchestrator/ClientAppointments). snake_case only here. */

interface ClientBody {
  id: number
  name: string
  email: string
  phone: string
  has_account: boolean
  emails_enabled?: boolean
}

interface SessionBody {
  access_token: string
  expires_in: number
  refresh_token: string
  refresh_expires_in: number
  client: ClientBody
}

interface AppointmentBody {
  id: number
  service_name: string
  date: string
  starts_at: string
  ends_at: string
  status: AppointmentStatus
  barbershop: {
    name: string
    slug: string
    address: string
    maps_url: string
    whatsapp_url: string
    avatar_url?: string | null
  }
}

const toClient = (body: ClientBody): ClientProfile => ({
  id: body.id,
  name: body.name,
  email: body.email,
  phone: body.phone,
  hasAccount: body.has_account,
  emailsEnabled: body.emails_enabled ?? true,
})

const toSession = (body: SessionBody, now = Date.now()): ClientSession => ({
  accessToken: body.access_token,
  refreshToken: body.refresh_token,
  accessExpiresAt: now + body.expires_in * 1000,
  refreshExpiresAt: now + body.refresh_expires_in * 1000,
  client: toClient(body.client),
})

const toAppointment = (body: AppointmentBody): ClientAppointment => ({
  id: body.id,
  serviceName: body.service_name,
  date: body.date,
  time: body.starts_at,
  endTime: body.ends_at,
  status: body.status,
  barbershop: {
    name: body.barbershop.name,
    slug: body.barbershop.slug,
    address: body.barbershop.address,
    mapsUrl: body.barbershop.maps_url,
    whatsappUrl: body.barbershop.whatsapp_url,
    avatarUrl: body.barbershop.avatar_url ?? null,
  },
})

/** Always the same answer (no account discovery); the e-mail only goes when it makes sense. */
export async function sendCode(email: string, purpose: CodePurpose, now = Date.now()): Promise<CodeSent> {
  const { data } = await http<{ resend_after: number }>('/client/codes', {
    method: 'POST',
    auth: false,
    body: { email, purpose },
  })
  return { resendAvailableAt: now + data.resend_after * 1000 }
}

/** Code + password: completes the sign-up (or sets a new password) and opens the session. */
export async function setPassword(purpose: CodePurpose, input: { email: string; code: string; password: string }): Promise<ClientSession> {
  const path = purpose === 'registration' ? '/client/registration' : '/client/password'
  const { data } = await http<SessionBody>(path, {
    method: 'POST',
    auth: false,
    body: input,
  })
  return toSession(data)
}

export async function login(email: string, password: string): Promise<ClientSession> {
  const { data } = await http<SessionBody>('/client/login', {
    method: 'POST',
    auth: false,
    body: { email, password },
  })
  return toSession(data)
}

export async function refreshSession(refreshToken: string): Promise<ClientSession> {
  const { data } = await http<SessionBody>('/client/token/refresh', {
    method: 'POST',
    auth: false,
    body: { refresh_token: refreshToken },
  })
  return toSession(data)
}

export async function logout(refreshToken: string): Promise<void> {
  await http<void>('/client/logout', {
    method: 'POST',
    auth: 'client',
    body: { refresh_token: refreshToken },
  })
}

/** Newest first. */
export async function fetchAppointments(): Promise<ClientAppointment[]> {
  const { data } = await http<AppointmentBody[]>('/client/appointments', {
    auth: 'client',
  })
  return data.map(toAppointment)
}

/** Only the client's own appointment, waiting or confirmed, until the cutoff before the time (CANCELLATION_TOO_LATE). */
export async function cancelAppointment(id: number): Promise<void> {
  await http<void>(`/client/appointments/${id}/cancel`, { method: 'POST', auth: 'client' })
}

/** "Minha conta": name and WhatsApp (as typed; the API keeps the digits). */
export async function updateProfile(input: { name: string; phone: string }): Promise<ClientProfile> {
  const { data } = await http<ClientBody>('/client/me', { method: 'PATCH', auth: 'client', body: input })
  return toClient(data)
}

/**
 * A wrong current password answers VALIDATION_FAILED on `current_password`. On success every other session ends
 * and this device gets a new one.
 */
export async function changePassword(input: { currentPassword: string; password: string }): Promise<ClientSession> {
  const { data } = await http<SessionBody>('/client/me/password', {
    method: 'PUT',
    auth: 'client',
    body: { current_password: input.currentPassword, password: input.password },
  })
  return toSession(data)
}

export async function updateEmailPreference(enabled: boolean): Promise<ClientProfile> {
  const { data } = await http<ClientBody>('/client/me/emails', { method: 'PUT', auth: 'client', body: { enabled } })
  return toClient(data)
}

/** LGPD: deletes the account after the password (the barbershops keep the history without the personal data). */
export async function deleteAccount(password: string): Promise<void> {
  await http<void>('/client/me', { method: 'DELETE', auth: 'client', body: { password } })
}

/** LGPD portability: the account and every appointment, as the API exports them. */
export async function exportMyData(): Promise<unknown> {
  const { data } = await http<unknown>('/client/me/export', { auth: 'client' })
  return data
}

/** The server key browsers need to subscribe (public: the client may not be signed in yet). */
export async function fetchClientPushKey(): Promise<string> {
  const { data } = await http<{ public_key: string }>('/public/push-key', { auth: false })
  return data.public_key
}

/**
 * Sends this device's subscription: with the signed link of a booking just made (no session needed, valid for a
 * short time), or for the signed-in client.
 */
export async function saveClientPushSubscription(subscription: PushSubscriptionJSON, pushUrl: string | null): Promise<void> {
  const body = { endpoint: subscription.endpoint, keys: subscription.keys }
  if (pushUrl) await http<void>(pushUrl.replace(/^\/api/, ''), { method: 'POST', body, auth: false })
  else await http<void>('/client/push-subscriptions', { method: 'POST', body, auth: 'client' })
}
