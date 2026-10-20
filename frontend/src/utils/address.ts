import type { BarbershopAddress } from '@/Domain/Settings/settings.model'

const EARTH_RADIUS_METERS = 6_371_000
const METERS_PER_KM = 1000

/** "01304001" → "01304-001" (anything else is returned as typed). */
export function formatZipCode(zipCode: string): string {
  const digits = zipCode.replace(/\D/g, '').slice(0, 8)
  return digits.length > 5 ? `${digits.slice(0, 5)}-${digits.slice(5)}` : digits
}

/** "Rua Augusta, 1500, sala 2 – Consolação, São Paulo/SP" */
export function formatAddress(address: BarbershopAddress): string {
  const street = [address.street, address.number, address.complement].map((part) => part.trim()).filter(Boolean).join(', ')
  return `${street} – ${address.district}, ${address.city}/${address.state}`
}

/** Google Maps search for the address (no key needed); the client picks the route there. */
export function mapsUrl(address: BarbershopAddress): string {
  const query = `${formatAddress(address)}, ${formatZipCode(address.zipCode)}`
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`
}

export interface Point {
  latitude: number
  longitude: number
}

/** Straight-line distance (haversine), same formula as the API. */
export function distanceInMeters(from: Point, to: Point): number {
  const toRadians = (degrees: number) => (degrees * Math.PI) / 180
  const latitudeDelta = toRadians(to.latitude - from.latitude)
  const longitudeDelta = toRadians(to.longitude - from.longitude)
  const a =
    Math.sin(latitudeDelta / 2) ** 2 +
    Math.cos(toRadians(from.latitude)) * Math.cos(toRadians(to.latitude)) * Math.sin(longitudeDelta / 2) ** 2
  return Math.round(2 * EARTH_RADIUS_METERS * Math.asin(Math.min(1, Math.sqrt(a))))
}

/** 850 → "850 m"; 3240 → "3,2 km"; 125400 → "125 km". */
export function formatDistance(meters: number): string {
  if (meters < METERS_PER_KM) return `${meters} m`
  const km = meters / METERS_PER_KM
  return `${km.toLocaleString('pt-BR', { maximumFractionDigits: km < 100 ? 1 : 0 })} km`
}
