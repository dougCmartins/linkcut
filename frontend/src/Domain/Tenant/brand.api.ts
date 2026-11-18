import { http } from '@/http/client'
import { toAddress, type AddressBody } from '@/Domain/Settings/settings.api'
import type { BarbershopAddress } from '@/Domain/Settings/settings.model'

/** One unit on the brand link's picker. Its own page lives at /{slug}. */
export interface BrandUnit {
  slug: string
  name: string
  isMain: boolean
  address: BarbershopAddress | null
  avatarUrl: string
}

/** A brand with branches and its active units, main unit first (docs/decisoes/0001 P2). */
export interface Brand {
  slug: string
  name: string
  units: BrandUnit[]
}

interface BrandBody {
  slug: string
  name: string
  units: { slug: string; name: string; is_main: boolean; address: AddressBody | null; avatar_url: string | null }[]
}

export async function fetchBrand(slug: string): Promise<Brand> {
  const { data } = await http<BrandBody>(`/public/brand/${encodeURIComponent(slug)}`, { auth: false })
  return {
    slug: data.slug,
    name: data.name,
    units: data.units.map((unit) => ({
      slug: unit.slug,
      name: unit.name,
      isMain: unit.is_main,
      address: unit.address ? toAddress(unit.address) : null,
      avatarUrl: unit.avatar_url ?? '',
    })),
  }
}
