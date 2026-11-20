import { http } from '@/http/client'
import type { BarbershopAddress } from '@/Domain/Settings/settings.model'
import { ApiError } from '@/http/apiError'

/** Public brand (organization) API — snake_case on the wire, camelCase in the SPA. */

export interface BrandUnit {
  name: string
  slug: string
  isMain: boolean
  address: BarbershopAddress | null
  photoUrl: string | null
}

export interface PublicBrand {
  name: string
  slug: string
  units: BrandUnit[]
}

interface AddressDto {
  zip_code: string
  street: string
  number: string
  complement: string | null
  district: string
  city: string
  state: string
  latitude: number | null
  longitude: number | null
}

interface BrandUnitDto {
  name: string
  slug: string
  is_main: boolean
  address: AddressDto | null
  photo_url: string | null
}

interface PublicBrandDto {
  name: string
  slug: string
  units: BrandUnitDto[]
}

function toAddress(body: AddressDto): BarbershopAddress {
  return {
    zipCode: body.zip_code,
    street: body.street,
    number: body.number,
    complement: body.complement ?? '',
    district: body.district,
    city: body.city,
    state: body.state,
    latitude: body.latitude,
    longitude: body.longitude,
  }
}

function mapUnit(dto: BrandUnitDto): BrandUnit {
  return {
    name: dto.name,
    slug: dto.slug,
    isMain: dto.is_main,
    address: dto.address ? toAddress(dto.address) : null,
    photoUrl: dto.photo_url,
  }
}

export async function fetchPublicBrand(slug: string): Promise<PublicBrand> {
  const { data } = await http<PublicBrandDto>(`/public/brand/${encodeURIComponent(slug)}`, { auth: false })
  return {
    name: data.name,
    slug: data.slug,
    units: data.units.map(mapUnit),
  }
}

/** Returns null when the slug is not a brand (404) or the call fails for another reason. */
export async function fetchPublicBrandOrNull(slug: string): Promise<PublicBrand | null> {
  try {
    return await fetchPublicBrand(slug)
  } catch (caught) {
    if (caught instanceof ApiError && caught.status === 404) return null
    return null
  }
}
