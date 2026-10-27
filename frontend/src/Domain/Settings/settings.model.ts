import type { ProfileLinkIcon } from '@/types/profileLinkIcon'
import type { SocialNetwork } from '@/types/socialNetwork'
import type { ButtonShape, ButtonStyle, ThemeMode, ThemePalette } from '@/types/theme'

export const SettingsSection = {
  Profile: 'profile',
  Portfolio: 'portfolio',
  Schedule: 'schedule',
  Theme: 'theme',
} as const

export type SettingsSection = (typeof SettingsSection)[keyof typeof SettingsSection]

export const SETTINGS_SECTIONS: SettingsSection[] = Object.values(SettingsSection)

/** Tabs of the settings screen. Values are the `?aba=` URL slugs. */
export const SettingsTab = {
  Link: 'link',
  Portfolio: 'portfolio',
  Schedule: 'agenda',
  Appearance: 'aparencia',
  Security: 'seguranca',
} as const

export type SettingsTab = (typeof SettingsTab)[keyof typeof SettingsTab]

/** Tabs saved with the save bar. Segurança saves right away. */
export const TAB_SECTIONS: Partial<Record<SettingsTab, SettingsSection>> = {
  [SettingsTab.Link]: SettingsSection.Profile,
  [SettingsTab.Portfolio]: SettingsSection.Portfolio,
  [SettingsTab.Schedule]: SettingsSection.Schedule,
  [SettingsTab.Appearance]: SettingsSection.Theme,
}

export const SaveResult = {
  Saved: 'saved',
  Invalid: 'invalid',
} as const

export type SaveResult = (typeof SaveResult)[keyof typeof SaveResult]

export const MAX_PORTFOLIO_PHOTOS = 4
export const SLOT_INTERVAL_OPTIONS = [15, 30, 45, 60] as const
export const BOOKING_WINDOW_OPTIONS = [7, 14, 21, 30] as const

/** Services not saved yet get a temporary id with this prefix; the API gives the real one. */
export const NEW_SERVICE_ID_PREFIX = 'new-'

/** An image uploaded to the API. */
export interface ImageRef {
  id: number
  url: string
}

export type ImageSource = ImageRef | null

export interface SocialLink {
  network: SocialNetwork
  url: string
}

/** A free button on the public page. `icon` is null when the barber picked none. */
export interface ProfileLink {
  title: string
  url: string
  icon: ProfileLinkIcon | null
}

/** The 27 Brazilian states (UF). */
export const BRAZIL_STATES = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
  'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
] as const

/**
 * Where the barbershop is: clients see it when they book (required to receive bookings).
 * The point on the map is null until it is found from the address or set with "Estou na barbearia".
 */
export interface BarbershopAddress {
  /** CEP, 8 digits. */
  zipCode: string
  street: string
  number: string
  complement: string
  district: string
  city: string
  /** UF, e.g. "SP". */
  state: string
  latitude: number | null
  longitude: number | null
}

export function emptyAddress(): BarbershopAddress {
  return { zipCode: '', street: '', number: '', complement: '', district: '', city: '', state: '', latitude: null, longitude: null }
}

/** Barbershops created before the address was required have none until the barber fills it in. */
export function hasAddress(address: BarbershopAddress | null): address is BarbershopAddress {
  return Boolean(address?.street.trim())
}

export interface ProfileSettings {
  name: string
  handle: string
  bio: string
  avatar: ImageSource
  background: ImageSource
  whatsappNumber: string
  whatsappMessage: string
  socials: SocialLink[]
  links: ProfileLink[]
  /** Always an object in the editor; empty fields when not filled in yet. */
  address: BarbershopAddress
}

export interface Highlight {
  label: string
  value: string
}

export interface PortfolioPhoto {
  imageId: number
  url: string
  alt: string
}

export interface PortfolioSettings {
  cover: ImageSource
  quote: string
  /** Paragraphs are separated by a blank line. */
  story: string
  highlights: Highlight[]
  photos: PortfolioPhoto[]
  /** Average under the public photo, and comments on the portfolio. Both start off. */
  showRating: boolean
  showReviews: boolean
}

/** A service the client can book. */
export interface Service {
  id: string
  name: string
  durationMinutes: number
  priceInCents: number
  /** Fixed cost per procedure (material, products), private to the barber. Missing in public data. */
  costInCents?: number
}

export interface DayHours {
  /** 0 = Sunday … 6 = Saturday (same as Date#getDay). */
  weekday: number
  isOpen: boolean
  opensAt: string
  closesAt: string
}

export interface VacationLock {
  isEnabled: boolean
  /** "YYYY-MM-DD". Both empty = locked until the barber turns it off. */
  startsOn: string | null
  endsOn: string | null
  message: string
}

export interface ScheduleSettings {
  services: Service[]
  weeklyHours: DayHours[]
  slotIntervalMinutes: number
  bookingWindowDays: number
  vacation: VacationLock
}

export interface ThemeSettings {
  palette: ThemePalette
  mode: ThemeMode
  /** "#rrggbb" or null to use the palette accent. */
  customAccent: string | null
  buttonShape: ButtonShape
  buttonStyle: ButtonStyle
}

export interface AppSettings {
  profile: ProfileSettings
  portfolio: PortfolioSettings
  schedule: ScheduleSettings
  theme: ThemeSettings
}

/** What a visitor sees on /:slug and /:slug/portfolio (no ids, no private data). */
export interface PublicProfile {
  name: string
  handle: string
  bio: string
  avatarUrl: string
  backgroundUrl: string
  whatsappNumber: string
  whatsappMessage: string
  socials: SocialLink[]
  links: ProfileLink[]
  /** Null when the barbershop has no address yet (then it can't receive bookings). */
  address: BarbershopAddress | null
}

export interface PublicPortfolio {
  coverUrl: string
  quote: string
  story: string
  highlights: Highlight[]
  photos: { url: string; alt: string }[]
}

export interface PublicRating {
  /** Null when the barber hides the average, or nobody has a visible rating. */
  average: number | null
  count: number
}

export interface FeaturedReview {
  firstName: string
  rating: number
  comment: string
}

export interface PublicPage {
  profile: PublicProfile
  portfolio: PublicPortfolio
  theme: ThemeSettings
  rating: PublicRating
  featuredReviews: FeaturedReview[]
}

export function imageUrl(source: ImageSource): string {
  return source?.url ?? ''
}

export function splitParagraphs(text: string): string[] {
  return text
    .split(/\n\s*\n/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean)
}
