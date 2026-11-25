import { http } from '@/http/client'
import type { BarbershopAddress } from '@/Domain/Settings/settings.model'

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

export interface NewUnitInput {
  name: string
  slug: string
  address: BarbershopAddress
  /** Copies the main unit's settings once (photos are not copied). */
  copySettings: boolean
}

export async function createUnit(input: NewUnitInput): Promise<OrganizationUnit> {
  const { address } = input
  const { data } = await http<UnitDto>('/organization/units', {
    method: 'POST',
    body: {
      name: input.name.trim(),
      slug: input.slug,
      copy_settings: input.copySettings,
      address: {
        zip_code: address.zipCode.replace(/\D/g, ''),
        street: address.street.trim(),
        number: address.number.trim(),
        complement: address.complement.trim() || null,
        district: address.district.trim(),
        city: address.city.trim(),
        state: address.state,
        // Only set by "Estou na barbearia"; otherwise the API finds the point from the address.
        latitude: address.latitude,
        longitude: address.longitude,
      },
    },
  })
  return mapUnit(data)
}
