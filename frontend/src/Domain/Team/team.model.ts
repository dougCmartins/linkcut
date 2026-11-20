export interface StaffPermissions {
  seePrices: boolean
  seeOwnCommission: boolean
  seeUnitAgenda: boolean
}

export interface StaffMember {
  id: number
  tenantId: number
  userId: number | null
  name: string
  avatar: string | null
  active: boolean
  pendingInvite: boolean
  permissions: StaffPermissions
}

export interface StaffInvitePreview {
  name: string
  email: string
  unitName: string
  expired: boolean
  accepted: boolean
}
