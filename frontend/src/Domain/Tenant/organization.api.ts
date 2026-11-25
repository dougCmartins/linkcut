import { http } from '@/http/client'

/** Organization API (snake_case on the wire, camelCase in the SPA). */
export type OrganizationType = 'individual' | 'multi_unit'

export interface OrganizationUnit {
  id: number
  name: string
  slug: string
  isMain: boolean
}

export interface OrganizationSummary {
  id: number
  type: OrganizationType
  name: string
  slug: string
  units: OrganizationUnit[]
}

interface UnitDto {
  id: number
  name: string
  slug: string
  is_main: boolean
}

interface OrganizationDto {
  id: number
  type: OrganizationType
  name: string
  slug: string
  units: UnitDto[]
}

function mapUnit(dto: UnitDto): OrganizationUnit {
  return { id: dto.id, name: dto.name, slug: dto.slug, isMain: dto.is_main }
}

export async function fetchOrganization(): Promise<OrganizationSummary> {
  const { data } = await http<OrganizationDto>('/organization')
  return {
    id: data.id,
    type: data.type,
    name: data.name,
    slug: data.slug,
    units: data.units.map(mapUnit),
  }
}
