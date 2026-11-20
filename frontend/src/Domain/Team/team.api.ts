import { http } from '@/http/client'
import { toSession, type SessionResponse } from '@/Domain/Identity/auth.api'
import type { Session } from '@/Domain/Identity/auth.model'

/** What the owner lets a barber see (all off by default). */
export interface StaffPermissions {
  seePrices: boolean
  seeOwnCommission: boolean
  seeUnitAgenda: boolean
}

export type StaffStatus = 'invited' | 'active' | 'inactive'

/** A barber of the current unit (docs/decisoes/0001 P3). */
export interface StaffMember {
  id: number
  name: string
  email: string
  status: StaffStatus
  isYou: boolean
  permissions: StaffPermissions
}

interface StaffBody {
  id: number
  name: string
  email: string
  status: StaffStatus
  is_you: boolean
  permissions: { see_prices: boolean; see_own_commission: boolean; see_unit_agenda: boolean }
}

const toMember = (body: StaffBody): StaffMember => ({
  id: body.id,
  name: body.name,
  email: body.email,
  status: body.status,
  isYou: body.is_you,
  permissions: {
    seePrices: body.permissions.see_prices,
    seeOwnCommission: body.permissions.see_own_commission,
    seeUnitAgenda: body.permissions.see_unit_agenda,
  },
})

export async function fetchStaff(): Promise<StaffMember[]> {
  const { data } = await http<StaffBody[]>('/team/staff')
  return data.map(toMember)
}

export async function inviteStaff(input: { name: string; email: string }): Promise<StaffMember> {
  const { data } = await http<StaffBody>('/team/staff', { method: 'POST', body: input })
  return toMember(data)
}

export async function updateStaff(id: number, input: { active: boolean; permissions: StaffPermissions }): Promise<StaffMember> {
  const { data } = await http<StaffBody>(`/team/staff/${id}`, {
    method: 'PUT',
    body: {
      active: input.active,
      permissions: {
        see_prices: input.permissions.seePrices,
        see_own_commission: input.permissions.seeOwnCommission,
        see_unit_agenda: input.permissions.seeUnitAgenda,
      },
    },
  })
  return toMember(data)
}

export async function resendInvitation(id: number): Promise<void> {
  await http<void>(`/team/staff/${id}/invitation`, { method: 'POST' })
}

/** The link of the invitation e-mail: the barber creates a password and is signed in. */
export async function acceptInvitation(input: { token: string; password: string; acceptTerms: boolean }): Promise<Session> {
  const { data } = await http<SessionResponse>('/team/invitations/accept', {
    method: 'POST',
    auth: false,
    body: { token: input.token, password: input.password, accept_terms: input.acceptTerms },
  })
  return toSession(data)
}
