import type { Service } from '@/Booking/booking.model'
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

/** Tabs saved with the save bar (local settings). Segurança saves on the server right away. */
export const TAB_SECTIONS: Partial<Record<SettingsTab, SettingsSection>> = {
  [SettingsTab.Link]: SettingsSection.Profile,
  [SettingsTab.Portfolio]: SettingsSection.Portfolio,
  [SettingsTab.Schedule]: SettingsSection.Schedule,
  [SettingsTab.Appearance]: SettingsSection.Theme,
}

export const SaveResult = {
  Saved: 'saved',
  /** Saved for this visit only: the browser refused to store it. */
  SessionOnly: 'sessionOnly',
  Invalid: 'invalid',
} as const

export type SaveResult = (typeof SaveResult)[keyof typeof SaveResult]

export const MAX_PORTFOLIO_PHOTOS = 4
export const SLOT_INTERVAL_OPTIONS = [15, 30, 45, 60] as const
export const BOOKING_WINDOW_OPTIONS = [7, 14, 21, 30] as const

/** Prefix for images uploaded by the barber (kept in IndexedDB); anything else is a plain URL. */
export const STORED_IMAGE_PREFIX = 'stored:'

/** An https URL, a stored upload ("stored:<id>") or nothing. */
export type ImageSource = string | null

export interface SocialLink {
  network: SocialNetwork
  url: string
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
}

export interface Highlight {
  label: string
  value: string
}

export interface PortfolioPhoto {
  id: string
  src: string
  alt: string
}

export interface PortfolioSettings {
  cover: ImageSource
  quote: string
  /** Paragraphs are separated by a blank line. */
  story: string
  highlights: Highlight[]
  photos: PortfolioPhoto[]
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

export function isStoredImage(source: ImageSource): source is string {
  return typeof source === 'string' && source.startsWith(STORED_IMAGE_PREFIX)
}

export function storedImageId(source: string): string {
  return source.slice(STORED_IMAGE_PREFIX.length)
}

export function splitParagraphs(text: string): string[] {
  return text
    .split(/\n\s*\n/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean)
}
