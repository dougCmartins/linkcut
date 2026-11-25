export interface BrandTotals {
  revenueInCents: number
  count: number
  averageTicketInCents: number
  commissionInCents: number
}

export interface BrandUnitReport {
  unitId: number
  name: string
  slug: string
  isMain: boolean
  totals: BrandTotals
}

export interface BrandReport {
  from: string
  to: string
  total: BrandTotals
  units: BrandUnitReport[]
}

export interface BrandRange {
  from: string
  to: string
}
