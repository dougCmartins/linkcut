import { http } from '@/http/client'

/** One unit of the brand (the main one or a branch). */
export interface Unit {
  id: number
  name: string
  slug: string
  isMain: boolean
  active: boolean
  onboarded: boolean
}

export type OrganizationType = 'individual' | 'multi_unit'

/** The unit picker: every unit for the owner of the brand; only their own unit for anybody else. */
export interface UnitList {
  organizationName: string
  organizationType: OrganizationType
  canManage: boolean
  units: Unit[]
}

interface UnitWire {
  id: number
  name: string
  slug: string
  is_main: boolean
  active: boolean
  onboarded: boolean
}

interface UnitListWire {
  organization_name: string
  organization_type: OrganizationType
  can_manage: boolean
  units: UnitWire[]
}

export async function fetchUnits(): Promise<UnitList> {
  const { data } = await http<UnitListWire>('/organization/units')
  return {
    organizationName: data.organization_name,
    organizationType: data.organization_type,
    canManage: data.can_manage,
    units: data.units.map((unit) => ({
      id: unit.id,
      name: unit.name,
      slug: unit.slug,
      isMain: unit.is_main,
      active: unit.active,
      onboarded: unit.onboarded,
    })),
  }
}
