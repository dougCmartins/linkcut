import { http } from '@/http/client'
import type { ImageRef } from '@/Domain/Settings/settings.model'
import type { CatalogProduct, CatalogProductInput, ShowcaseProduct } from './catalog.model'

/** Catalog API (backend: Domain/Catalog + Orchestrator/Catalog). snake_case only here. */

interface ProductBody {
  id: number
  name: string
  price_in_cents: number
  description: string | null
  image: ImageRef | null
  is_active: boolean
  position: number
}

interface ProductInputBody {
  name: string
  price_in_cents: number
  description: string | null
  image_id: number | null
  is_active: boolean
}

const toProduct = (body: ProductBody): CatalogProduct => ({
  id: body.id,
  name: body.name,
  priceInCents: body.price_in_cents,
  description: body.description ?? '',
  image: body.image,
  isActive: body.is_active,
  position: body.position,
})

const toBody = (input: CatalogProductInput): ProductInputBody => ({
  name: input.name,
  price_in_cents: input.priceInCents,
  description: input.description.trim() ? input.description.trim() : null,
  image_id: input.imageId,
  is_active: input.isActive,
})

export async function fetchProducts(): Promise<CatalogProduct[]> {
  const { data } = await http<ProductBody[]>('/products')
  return data.map(toProduct)
}

export async function createProduct(input: CatalogProductInput): Promise<CatalogProduct> {
  const { data } = await http<ProductBody>('/products', { method: 'POST', body: toBody(input) })
  return toProduct(data)
}

export async function updateProduct(id: number, input: CatalogProductInput): Promise<CatalogProduct> {
  const { data } = await http<ProductBody>(`/products/${id}`, { method: 'PUT', body: toBody(input) })
  return toProduct(data)
}

export async function deleteProduct(id: number): Promise<void> {
  await http(`/products/${id}`, { method: 'DELETE' })
}

export async function reorderProducts(ids: number[]): Promise<CatalogProduct[]> {
  const { data } = await http<ProductBody[]>('/products/order', { method: 'PUT', body: { ids } })
  return data.map(toProduct)
}

interface ShowcaseProductBody {
  id: number
  name: string
  price_in_cents: number
  description: string | null
  image_url: string | null
}

/** Active products of a barbershop the client already booked. */
export async function fetchClientProducts(slug: string): Promise<ShowcaseProduct[]> {
  const { data } = await http<ShowcaseProductBody[]>(`/client/barbershops/${encodeURIComponent(slug)}/products`, { auth: 'client' })
  return data.map((product) => ({
    id: product.id,
    name: product.name,
    priceInCents: product.price_in_cents,
    description: product.description ?? '',
    imageUrl: product.image_url ?? '',
  }))
}
