import { installFakeApi, type FakeCall, type FakeHandler } from '@/utils/testFetch'
import { SocialNetwork } from '@/types/socialNetwork'
import { ButtonShape, ButtonStyle, ThemeMode, ThemePalette } from '@/types/theme'
import type { AppSettings, ImageRef, ScheduleSettings } from '../settings.model'

/** A fake Settings API with the same contract as the backend (snake_case, private cost, public pages by slug). */

export const SLUG = 'rafael'
export const TENANT = { id: 3, name: 'Rafael Costa', slug: SLUG, active: true, timezone: 'America/Sao_Paulo' }

export const testImage = (id: number): ImageRef => ({ id, url: `https://cdn.test/images/${id}.webp` })

/** Settings of a barbershop, as the SPA holds them. */
export function createTestSettings(): AppSettings {
  return {
    profile: {
      name: 'Rafael Costa',
      handle: '@rafa.barber',
      bio: 'Cortes clássicos e degradês precisos.',
      avatar: testImage(1),
      background: testImage(2),
      whatsappNumber: '11999998888',
      whatsappMessage: 'Olá! Vim pelo seu link e tenho uma dúvida.',
      socials: [
        { network: SocialNetwork.Instagram, url: 'https://instagram.com/rafa' },
        { network: SocialNetwork.TikTok, url: '' },
        { network: SocialNetwork.YouTube, url: '' },
      ],
      links: [],
      address: {
        zipCode: '01304001',
        street: 'Rua Augusta',
        number: '1500',
        complement: '',
        district: 'Consolação',
        city: 'São Paulo',
        state: 'SP',
        latitude: -23.5578,
        longitude: -46.6609,
      },
    },
    portfolio: {
      cover: testImage(3),
      quote: 'Um bom corte mostra quem você já é.',
      story: 'Comecei na cadeira do meu pai.\n\nHoje atendo sozinho, sem pressa.',
      highlights: [
        { label: 'Estilo', value: 'Clássico moderno' },
        { label: 'Foco', value: 'Degradê & barba' },
        { label: 'Agenda', value: 'Exclusiva' },
      ],
      photos: [{ imageId: 4, url: testImage(4).url, alt: 'Degradê com risca lateral' }],
      showRating: false,
      showReviews: false,
    },
    schedule: {
      services: [
        { id: '1', name: 'Corte', durationMinutes: 45, priceInCents: 5000, costInCents: 600 },
        { id: '2', name: 'Barba', durationMinutes: 30, priceInCents: 4000, costInCents: 400 },
      ],
      weeklyHours: Array.from({ length: 7 }, (_, weekday) => ({
        weekday,
        isOpen: weekday !== 0,
        opensAt: '09:00',
        closesAt: weekday === 6 ? '14:00' : '19:00',
      })),
      slotIntervalMinutes: 30,
      bookingWindowDays: 7,
      vacation: { isEnabled: false, startsOn: null, endsOn: null, message: 'Estou de férias!' },
    },
    theme: {
      palette: ThemePalette.Default,
      mode: ThemeMode.Dark,
      customAccent: null,
      buttonShape: ButtonShape.Pill,
      buttonStyle: ButtonStyle.Fill,
    },
  }
}

function scheduleBody(schedule: ScheduleSettings, withCost: boolean) {
  return {
    services: schedule.services.map((service) => ({
      id: Number(service.id),
      name: service.name,
      duration_minutes: service.durationMinutes,
      price_in_cents: service.priceInCents,
      ...(withCost ? { cost_in_cents: service.costInCents ?? 0 } : {}),
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

const themeBody = (theme: AppSettings['theme']) => ({
  palette: theme.palette,
  mode: theme.mode,
  custom_accent: theme.customAccent,
  button_shape: theme.buttonShape,
  button_style: theme.buttonStyle,
})

/** null when the street is empty (a barbershop without address), like the API. */
const addressBody = ({ profile: { address } }: AppSettings) =>
  address.street
    ? {
        zip_code: address.zipCode,
        street: address.street,
        number: address.number,
        complement: address.complement || null,
        district: address.district,
        city: address.city,
        state: address.state,
        latitude: address.latitude,
        longitude: address.longitude,
      }
    : null

const socialsBody = (settings: AppSettings) =>
  settings.profile.socials.map((social) => ({ network: social.network, url: social.url || null }))

const linksBody = (settings: AppSettings) =>
  settings.profile.links.map((link) => ({ title: link.title, url: link.url, icon: link.icon }))

/** GET /settings */
export function settingsBody(settings = createTestSettings()) {
  const { profile, portfolio } = settings
  return {
    profile: {
      name: profile.name,
      handle: profile.handle,
      bio: profile.bio,
      avatar: profile.avatar,
      background: profile.background,
      whatsapp_number: profile.whatsappNumber,
      whatsapp_message: profile.whatsappMessage,
      socials: socialsBody(settings),
      links: linksBody(settings),
      address: addressBody(settings),
    },
    portfolio: {
      cover: portfolio.cover,
      quote: portfolio.quote,
      story: portfolio.story,
      highlights: portfolio.highlights,
      photos: portfolio.photos.map((photo) => ({ image_id: photo.imageId, url: photo.url, alt: photo.alt })),
      show_rating: portfolio.showRating,
      show_reviews: portfolio.showReviews,
    },
    schedule: scheduleBody(settings.schedule, true),
    theme: themeBody(settings.theme),
  }
}

/** GET /public/{slug}: urls instead of image ids, only socials with a link, no cost anywhere. */
export function publicPageBody(settings = createTestSettings()) {
  const { profile, portfolio } = settings
  return {
    profile: {
      name: profile.name,
      handle: profile.handle,
      bio: profile.bio,
      avatar_url: profile.avatar?.url ?? null,
      background_url: profile.background?.url ?? null,
      whatsapp_number: profile.whatsappNumber,
      whatsapp_message: profile.whatsappMessage,
      socials: socialsBody(settings).filter((social) => social.url),
      links: linksBody(settings),
      address: addressBody(settings),
    },
    portfolio: {
      cover_url: portfolio.cover?.url ?? null,
      quote: portfolio.quote,
      story: portfolio.story,
      highlights: portfolio.highlights,
      photos: portfolio.photos.map((photo) => ({ url: photo.url, alt: photo.alt })),
    },
    theme: themeBody(settings.theme),
    rating: { average: null as number | null, count: 0 },
    featured_reviews: [] as { first_name: string; rating: number; comment: string }[],
  }
}

type Body = Record<string, unknown>

/**
 * Serves the private settings (saving keeps the new state), uploads, the tenant and the public pages of SLUG.
 * Any other path (e.g. another slug) answers 404, like the API.
 */
export function installFakeSettingsApi(settings = createTestSettings(), extraRoutes: Record<string, FakeHandler> = {}) {
  const current = settingsBody(settings)
  let nextId = 100
  const imageOf = (id: unknown) => (typeof id === 'number' ? testImage(id) : null)

  const fake = installFakeApi({
    'GET /settings': () => ({ status: 200, body: current }),
    'PUT /settings/profile': ({ body }: FakeCall) => {
      const input = body as Body
      current.profile = {
        ...(input as typeof current.profile),
        avatar: imageOf(input.avatar_image_id),
        background: imageOf(input.background_image_id),
      }
      return { status: 200, body: current.profile }
    },
    'PUT /settings/portfolio': ({ body }: FakeCall) => {
      const input = body as Body & { photos: { image_id: number; alt: string }[] }
      current.portfolio = {
        ...(input as unknown as typeof current.portfolio),
        cover: imageOf(input.cover_image_id),
        photos: input.photos.map((photo) => ({ ...photo, url: testImage(photo.image_id).url })),
      }
      return { status: 200, body: current.portfolio }
    },
    'PUT /settings/schedule': ({ body }: FakeCall) => {
      const input = body as unknown as typeof current.schedule & { services: { id: number | null }[] }
      current.schedule = {
        ...input,
        services: input.services.map((service) => ({ ...service, id: service.id ?? nextId++ })) as typeof current.schedule.services,
      }
      return { status: 200, body: current.schedule }
    },
    'PUT /settings/theme': ({ body }: FakeCall) => {
      current.theme = body as typeof current.theme
      return { status: 200, body: current.theme }
    },
    'POST /settings/images': () => ({ status: 201, body: testImage(nextId++) }),
    'GET /tenant': () => ({ status: 200, body: TENANT }),
    [`GET /public/${SLUG}`]: () => ({ status: 200, body: publicPageBody(settings) }),
    [`GET /public/${SLUG}/booking-options`]: () => ({ status: 200, body: scheduleBody(settings.schedule, false) }),
    ...extraRoutes,
  })

  return { ...fake, current }
}
