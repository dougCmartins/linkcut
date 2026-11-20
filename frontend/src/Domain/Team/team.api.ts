import { http } from '@/http/client'
import { toSession, type SessionResponse } from '@/Domain/Identity/auth.api'
import type { Session } from '@/Domain/Identity/auth.model'
import type { StaffInvitePreview, StaffMember, StaffPermissions } from './team.model'

interface PermissionsBody {
  see_prices: boolean
  see_own_commission: boolean
  see_unit_agenda: boolean
}

interface StaffBody {
  id: number
  tenant_id: number
  user_id: number | null
  name: string
  avatar: string | null
  active: boolean
  pending_invite: boolean
  permissions: PermissionsBody
}

interface InvitePreviewBody {
  name: string
  email: string
  unit_name: string
  expired: boolean
  accepted: boolean
}

function toPermissions(body: PermissionsBody): StaffPermissions {
  return {
    seePrices: body.see_prices,
    seeOwnCommission: body.see_own_commission,
    seeUnitAgenda: body.see_unit_agenda,
  }
}

function toStaff(body: StaffBody): StaffMember {
  return {
    id: body.id,
    tenantId: body.tenant_id,
    userId: body.user_id,
    name: body.name,
    avatar: body.avatar,
    active: body.active,
    pendingInvite: body.pending_invite,
    permissions: toPermissions(body.permissions),
  }
}

export async function fetchStaff(): Promise<StaffMember[]> {
  const { data } = await http<StaffBody[]>('/team/staff')
  return data.map(toStaff)
}

export async function inviteStaff(name: string, email: string): Promise<StaffMember> {
  const { data } = await http<StaffBody>('/team/staff', {
    method: 'POST',
    body: { name, email },
  })
  return toStaff(data)
}

export async function updateStaffActive(id: number, active: boolean): Promise<StaffMember> {
  const { data } = await http<StaffBody>(`/team/staff/${id}/active`, {
    method: 'PATCH',
    body: { active },
  })
  return toStaff(data)
}

export async function updateStaffPermissions(id: number, permissions: StaffPermissions): Promise<StaffMember> {
  const { data } = await http<StaffBody>(`/team/staff/${id}/permissions`, {
    method: 'PATCH',
    body: {
      see_prices: permissions.seePrices,
      see_own_commission: permissions.seeOwnCommission,
      see_unit_agenda: permissions.seeUnitAgenda,
    },
  })
  return toStaff(data)
}

export async function fetchStaffInvite(token: string): Promise<StaffInvitePreview> {
  const { data } = await http<InvitePreviewBody>(`/public/staff-invites/${token}`, { auth: false })
  return {
    name: data.name,
    email: data.email,
    unitName: data.unit_name,
    expired: data.expired,
    accepted: data.accepted,
  }
}

export async function acceptStaffInvite(token: string, name: string, password: string): Promise<Session> {
  const { data } = await http<SessionResponse>(`/public/staff-invites/${token}`, {
    method: 'POST',
    body: { name, password },
    auth: false,
  })
  return toSession(data)
}
