import type { ImageRef } from '@/Domain/Settings/settings.model'

/** A product as the panel edits it. */
export interface CatalogProduct {
  id: number
  name: string
  priceInCents: number
  description: string
  image: ImageRef | null
  isActive: boolean
  position: number
}

/** An active product as the client sees it. */
export interface ShowcaseProduct {
  id: number
  name: string
  priceInCents: number
  description: string
  imageUrl: string
}

/** Fields the form sends. The API cleans the description. */
export interface CatalogProductInput {
  name: string
  priceInCents: number
  description: string
  imageId: number | null
  isActive: boolean
}
