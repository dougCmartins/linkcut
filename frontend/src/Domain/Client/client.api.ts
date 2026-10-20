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
  status: AppointmentStatus
  barbershop: {
    name: string
    slug: string
    address: string
    maps_url: string
    whatsapp_url: string
  }
}

const toClient = (body: ClientBody): ClientProfile => ({
  id: body.id,
  name: body.name,
  email: body.email,
  phone: body.phone,
  hasAccount: body.has_account,
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
  status: body.status,
  barbershop: {
    name: body.barbershop.name,
    slug: body.barbershop.slug,
    address: body.barbershop.address,
    mapsUrl: body.barbershop.maps_url,
    whatsappUrl: body.barbershop.whatsapp_url,
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
