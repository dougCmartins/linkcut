import { http } from '@/http/client'
import type { ProfileLinkIcon } from '@/types/profileLinkIcon'
import type { SocialNetwork } from '@/types/socialNetwork'
import type { ButtonShape, ButtonStyle, ThemeMode, ThemePalette } from '@/types/theme'
import {
  NEW_SERVICE_ID_PREFIX,
  emptyAddress,
  type AppSettings,
  type BarbershopAddress,
  type DayHours,
  type ImageRef,
  type PortfolioSettings,
  type ProfileLink,
  type ProfileSettings,
  type PublicPage,
  type ScheduleSettings,
  type Service,
  type SettingsSection,
  type SocialLink,
  type ThemeSettings,
} from './settings.model'

/** Settings API (snake_case on the wire, camelCase in the SPA). Mapping happens only in this file. */

interface SocialLinkBody {
  network: SocialNetwork
  url: string | null
}

interface ProfileLinkBody {
  title: string
  url: string
  icon: ProfileLinkIcon | null
}

interface HighlightBody {
  label: string | null
  value: string | null
}

interface AddressBody {
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

interface ProfileBody {
  address: AddressBody | null
  name: string
  handle: string | null
  bio: string | null
  avatar: ImageRef | null
  background: ImageRef | null
  whatsapp_number: string
  whatsapp_message: string | null
  socials: SocialLinkBody[]
  links: ProfileLinkBody[]
}

interface PortfolioBody {
  cover: ImageRef | null
  quote: string | null
  story: string | null
  highlights: HighlightBody[]
  photos: { image_id: number; url: string; alt: string }[]
  show_rating?: boolean
  show_reviews?: boolean
}

interface ServiceBody {
  id: number
  name: string
  duration_minutes: number
  price_in_cents: number
  /** Only in the private settings, never in the public booking options. */
  cost_in_cents?: number
}

interface DayHoursBody {
  weekday: number
  is_open: boolean
  opens_at: string
  closes_at: string
}

interface ScheduleBody {
  services: ServiceBody[]
  weekly_hours: DayHoursBody[]
  slot_interval_minutes: number
  booking_window_days: number
  vacation: { is_enabled: boolean; starts_on: string | null; ends_on: string | null; message: string | null }
}

interface ThemeBody {
  palette: ThemePalette
  mode: ThemeMode
  custom_accent: string | null
  button_shape: ButtonShape
  button_style: ButtonStyle
}

interface SettingsBody {
  profile: ProfileBody
  portfolio: PortfolioBody
  schedule: ScheduleBody
  theme: ThemeBody
}

interface PublicPageBody {
  profile: Omit<ProfileBody, 'avatar' | 'background'> & { avatar_url: string | null; background_url: string | null }
  portfolio: Omit<PortfolioBody, 'cover' | 'photos'> & { cover_url: string | null; photos: { url: string; alt: string }[] }
  theme: ThemeBody
  rating?: { average: number | null; count: number }
  featured_reviews?: { first_name: string; rating: number; comment: string }[]
}

const toSocials = (socials: SocialLinkBody[]): SocialLink[] =>
  socials.map((social) => ({ network: social.network, url: social.url ?? '' }))

const toLinks = (links: ProfileLinkBody[]): ProfileLink[] =>
  links.map((link) => ({ title: link.title, url: link.url, icon: link.icon }))

const toAddress = (body: AddressBody): BarbershopAddress => ({
  zipCode: body.zip_code,
  street: body.street,
  number: body.number,
  complement: body.complement ?? '',
  district: body.district,
  city: body.city,
  state: body.state,
  latitude: body.latitude,
  longitude: body.longitude,
})

const toHighlights = (highlights: HighlightBody[]) =>
  highlights.map((highlight) => ({ label: highlight.label ?? '', value: highlight.value ?? '' }))

function toProfile(body: ProfileBody): ProfileSettings {
  return {
    name: body.name,
    handle: body.handle ?? '',
    bio: body.bio ?? '',
    avatar: body.avatar,
    background: body.background,
    whatsappNumber: body.whatsapp_number,
    whatsappMessage: body.whatsapp_message ?? '',
    socials: toSocials(body.socials),
    links: toLinks(body.links),
    address: body.address ? toAddress(body.address) : emptyAddress(),
  }
}

function toPortfolio(body: PortfolioBody): PortfolioSettings {
  return {
    cover: body.cover,
    quote: body.quote ?? '',
    story: body.story ?? '',
    highlights: toHighlights(body.highlights),
    photos: body.photos.map((photo) => ({ imageId: photo.image_id, url: photo.url, alt: photo.alt })),
    showRating: body.show_rating ?? false,
    showReviews: body.show_reviews ?? false,
  }
}

function toService(body: ServiceBody): Service {
  return {
    id: String(body.id),
    name: body.name,
    durationMinutes: body.duration_minutes,
    priceInCents: body.price_in_cents,
    costInCents: body.cost_in_cents,
  }
}

function toDayHours(body: DayHoursBody): DayHours {
  return { weekday: body.weekday, isOpen: body.is_open, opensAt: body.opens_at, closesAt: body.closes_at }
}

/** Also used for the public booking options (same shape, services without cost). */
function toSchedule(body: ScheduleBody): ScheduleSettings {
  return {
    services: body.services.map(toService),
    weeklyHours: body.weekly_hours.map(toDayHours),
    slotIntervalMinutes: body.slot_interval_minutes,
    bookingWindowDays: body.booking_window_days,
    vacation: {
      isEnabled: body.vacation.is_enabled,
      startsOn: body.vacation.starts_on,
      endsOn: body.vacation.ends_on,
      message: body.vacation.message ?? '',
    },
  }
}

function toTheme(body: ThemeBody): ThemeSettings {
  return {
    palette: body.palette,
    mode: body.mode,
    customAccent: body.custom_accent,
    buttonShape: body.button_shape,
    buttonStyle: body.button_style,
  }
}

function profileBody(profile: ProfileSettings) {
  return {
    name: profile.name,
    handle: profile.handle,
    bio: profile.bio,
    avatar_image_id: profile.avatar?.id ?? null,
    background_image_id: profile.background?.id ?? null,
    whatsapp_number: profile.whatsappNumber,
    whatsapp_message: profile.whatsappMessage,
    socials: profile.socials.map((social) => ({ network: social.network, url: social.url.trim() || null })),
    links: profile.links.map((link) => ({ title: link.title.trim(), url: link.url.trim(), icon: link.icon })),
    address: {
      zip_code: profile.address.zipCode.replace(/\D/g, ''),
      street: profile.address.street.trim(),
      number: profile.address.number.trim(),
      complement: profile.address.complement.trim() || null,
      district: profile.address.district.trim(),
      city: profile.address.city.trim(),
      state: profile.address.state,
      // Only set by "Estou na barbearia"; otherwise the API finds the point from the address.
      latitude: profile.address.latitude,
      longitude: profile.address.longitude,
    },
  }
}

function portfolioBody(portfolio: PortfolioSettings) {
  return {
    cover_image_id: portfolio.cover?.id ?? null,
    quote: portfolio.quote,
    story: portfolio.story,
    highlights: portfolio.highlights,
    photos: portfolio.photos.map((photo) => ({ image_id: photo.imageId, alt: photo.alt })),
    show_rating: portfolio.showRating,
    show_reviews: portfolio.showReviews,
  }
}

function scheduleBody(schedule: ScheduleSettings) {
  return {
    services: schedule.services.map((service) => ({
      id: service.id.startsWith(NEW_SERVICE_ID_PREFIX) ? null : Number(service.id),
      name: service.name,
      duration_minutes: service.durationMinutes,
      price_in_cents: service.priceInCents,
      cost_in_cents: service.costInCents ?? 0,
    })),
    weekly_hours: schedule.weeklyHours.map((day) => ({
      weekday: day.weekday,
      is_open: day.isOpen,
      opens_at: day.opensAt,
      closes_at: day.closesAt,
    })),
    slot_interval_minutes: schedule.slotIntervalMinutes,
    booking_window_days: schedule.bookingWindowDays,
    vacation: {
      is_enabled: schedule.vacation.isEnabled,
      starts_on: schedule.vacation.startsOn,
      ends_on: schedule.vacation.endsOn,
      message: schedule.vacation.message,
    },
  }
}

function themeBody(theme: ThemeSettings) {
  return {
    palette: theme.palette,
    mode: theme.mode,
    custom_accent: theme.customAccent,
    button_shape: theme.buttonShape,
    button_style: theme.buttonStyle,
  }
}

/** How each section goes to the API (PUT /settings/{section}) and comes back. */
const SECTIONS: { [K in SettingsSection]: { toBody: (value: AppSettings[K]) => unknown; fromBody: (body: unknown) => AppSettings[K] } } = {
  profile: { toBody: profileBody, fromBody: (body) => toProfile(body as ProfileBody) },
  portfolio: { toBody: portfolioBody, fromBody: (body) => toPortfolio(body as PortfolioBody) },
  schedule: { toBody: scheduleBody, fromBody: (body) => toSchedule(body as ScheduleBody) },
  theme: { toBody: themeBody, fromBody: (body) => toTheme(body as ThemeBody) },
}

export async function fetchSettings(): Promise<AppSettings> {
  const { data } = await http<SettingsBody>('/settings')
  return {
    profile: toProfile(data.profile),
    portfolio: toPortfolio(data.portfolio),
    schedule: toSchedule(data.schedule),
    theme: toTheme(data.theme),
  }
}

/** Saves a whole section and returns it as the API stored it (e.g. new services with their ids). */
export async function saveSection<K extends SettingsSection>(section: K, value: AppSettings[K]): Promise<AppSettings[K]> {
  const codec = SECTIONS[section]
  const { data } = await http<unknown>(`/settings/${section}`, { method: 'PUT', body: codec.toBody(value) })
  return codec.fromBody(data)
}

export async function uploadImage(file: Blob): Promise<ImageRef> {
  const form = new FormData()
  form.append('file', file, file instanceof File ? file.name : 'image.webp')
  const { data } = await http<ImageRef>('/settings/images', { method: 'POST', body: form })
  return data
}

export async function fetchPublicPage(slug: string): Promise<PublicPage> {
  const { data } = await http<PublicPageBody>(`/public/${encodeURIComponent(slug)}`, { auth: false })
  return {
    profile: {
      name: data.profile.name,
      handle: data.profile.handle ?? '',
      bio: data.profile.bio ?? '',
      avatarUrl: data.profile.avatar_url ?? '',
      backgroundUrl: data.profile.background_url ?? '',
      whatsappNumber: data.profile.whatsapp_number,
      whatsappMessage: data.profile.whatsapp_message ?? '',
      socials: toSocials(data.profile.socials),
      links: toLinks(data.profile.links),
      address: data.profile.address ? toAddress(data.profile.address) : null,
    },
    portfolio: {
      coverUrl: data.portfolio.cover_url ?? '',
      quote: data.portfolio.quote ?? '',
      story: data.portfolio.story ?? '',
      highlights: toHighlights(data.portfolio.highlights),
      photos: data.portfolio.photos,
    },
    theme: toTheme(data.theme),
    rating: {
      average: typeof data.rating?.average === 'number' ? data.rating.average : null,
      count: data.rating?.count ?? 0,
    },
    featuredReviews: (data.featured_reviews ?? []).map((review) => ({
      firstName: review.first_name,
      rating: review.rating,
      comment: review.comment,
    })),
  }
}

/** Services (without cost), hours, rules and vacation for the booking modal. */
export async function fetchBookingOptions(slug: string): Promise<ScheduleSettings> {
  const { data } = await http<ScheduleBody>(`/public/${encodeURIComponent(slug)}/booking-options`, { auth: false })
  return toSchedule(data)
}
