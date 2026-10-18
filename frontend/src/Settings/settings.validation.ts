import {
  MAX_PORTFOLIO_PHOTOS,
  type AppSettings,
  type PortfolioSettings,
  type ProfileSettings,
  type ScheduleSettings,
  type SettingsSection,
  type ThemeSettings,
} from './settings.model'
import { BUTTON_SHAPES, BUTTON_STYLES } from '@/types/theme'
import { isHexColor } from '@/utils/color'
import { isValidWhatsappNumber } from '@/utils/phone'
import { toMinutes } from '@/utils/time'

/** Field limits shared by the validators and the form inputs (maxlength, counters). */
export const TEXT_LIMITS = {
  name: { min: 2, max: 40 },
  handle: { max: 30 },
  bio: { max: 160 },
  whatsappMessage: { max: 200 },
  quote: { max: 200 },
  story: { max: 1200 },
  highlightLabel: { max: 16 },
  highlightValue: { max: 24 },
  photoAlt: { max: 80 },
  serviceName: { max: 30 },
  vacationMessage: { max: 160 },
} as const

export const SERVICE_LIMITS = {
  minDuration: 5,
  maxDuration: 240,
  durationStep: 5,
  maxPriceInCents: 1_000_000,
} as const

export interface ValidationError {
  /** i18n key under `settings.errors`. */
  key: string
  params?: Record<string, string | number>
}

/** Errors by field path, e.g. "name", "services.0.priceInCents", "weeklyHours.1". */
export type ValidationErrors = Record<string, ValidationError>

type Collector = (field: string, error: ValidationError | null) => void

function createCollector(): { errors: ValidationErrors; add: Collector } {
  const errors: ValidationErrors = {}
  return {
    errors,
    add: (field, error) => {
      if (error) errors[field] = error
    },
  }
}

function checkLength(value: string, limits: { min?: number; max: number }, required = false): ValidationError | null {
  const length = value.trim().length
  if (required && length === 0) return { key: 'required' }
  if (limits.min !== undefined && length > 0 && length < limits.min) return { key: 'tooShort', params: { min: limits.min } }
  if (length > limits.max) return { key: 'tooLong', params: { max: limits.max } }
  return null
}

export function validateProfile(profile: ProfileSettings): ValidationErrors {
  const { errors, add } = createCollector()
  add('name', checkLength(profile.name, TEXT_LIMITS.name, true))
  add('handle', checkLength(profile.handle, TEXT_LIMITS.handle))
  add('bio', checkLength(profile.bio, TEXT_LIMITS.bio))
  add('whatsappNumber', isValidWhatsappNumber(profile.whatsappNumber) ? null : { key: 'phone' })
  add('whatsappMessage', checkLength(profile.whatsappMessage, TEXT_LIMITS.whatsappMessage))
  profile.socials.forEach((social, index) => {
    const url = social.url.trim()
    add(`socials.${index}`, url && !url.startsWith('https://') ? { key: 'https' } : null)
  })
  return errors
}

export function validatePortfolio(portfolio: PortfolioSettings): ValidationErrors {
  const { errors, add } = createCollector()
  add('quote', checkLength(portfolio.quote, TEXT_LIMITS.quote))
  add('story', checkLength(portfolio.story, TEXT_LIMITS.story))
  portfolio.highlights.forEach((highlight, index) => {
    add(`highlights.${index}.label`, checkLength(highlight.label, TEXT_LIMITS.highlightLabel))
    add(`highlights.${index}.value`, checkLength(highlight.value, TEXT_LIMITS.highlightValue))
  })
  add(
    'photos',
    portfolio.photos.length > MAX_PORTFOLIO_PHOTOS ? { key: 'tooManyPhotos', params: { max: MAX_PORTFOLIO_PHOTOS } } : null,
  )
  portfolio.photos.forEach((photo, index) => {
    add(`photos.${index}.alt`, photo.alt.trim() ? checkLength(photo.alt, TEXT_LIMITS.photoAlt) : { key: 'altRequired' })
  })
  return errors
}

export function validateSchedule(schedule: ScheduleSettings): ValidationErrors {
  const { errors, add } = createCollector()
  add('services', schedule.services.length === 0 ? { key: 'minServices' } : null)
  schedule.services.forEach((service, index) => {
    add(`services.${index}.name`, checkLength(service.name, TEXT_LIMITS.serviceName, true))
    const { minDuration, maxDuration, durationStep, maxPriceInCents } = SERVICE_LIMITS
    const duration = service.durationMinutes
    const isDurationValid =
      Number.isInteger(duration) && duration >= minDuration && duration <= maxDuration && duration % durationStep === 0
    add(
      `services.${index}.durationMinutes`,
      isDurationValid ? null : { key: 'duration', params: { min: minDuration, max: maxDuration, step: durationStep } },
    )
    const price = service.priceInCents
    add(
      `services.${index}.priceInCents`,
      Number.isFinite(price) && price > 0 && price <= maxPriceInCents ? null : { key: 'price' },
    )
  })
  schedule.weeklyHours.forEach((day, index) => {
    const isOrdered = toMinutes(day.opensAt) < toMinutes(day.closesAt)
    add(`weeklyHours.${index}`, day.isOpen && (!day.opensAt || !day.closesAt || !isOrdered) ? { key: 'hoursOrder' } : null)
  })
  const { vacation } = schedule
  add(
    'vacation.endsOn',
    vacation.startsOn && vacation.endsOn && vacation.endsOn < vacation.startsOn ? { key: 'vacationOrder' } : null,
  )
  add('vacation.message', checkLength(vacation.message, TEXT_LIMITS.vacationMessage))
  return errors
}

export function validateTheme(theme: ThemeSettings): ValidationErrors {
  const { errors, add } = createCollector()
  add('customAccent', theme.customAccent !== null && !isHexColor(theme.customAccent) ? { key: 'hexColor' } : null)
  add('buttonShape', BUTTON_SHAPES.includes(theme.buttonShape) ? null : { key: 'invalidOption' })
  add('buttonStyle', BUTTON_STYLES.includes(theme.buttonStyle) ? null : { key: 'invalidOption' })
  return errors
}

export const sectionValidators: { [K in SettingsSection]: (value: AppSettings[K]) => ValidationErrors } = {
  profile: validateProfile,
  portfolio: validatePortfolio,
  schedule: validateSchedule,
  theme: validateTheme,
}
