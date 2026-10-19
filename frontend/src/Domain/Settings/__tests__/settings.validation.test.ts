import { describe, expect, it } from 'vitest'
import { createTestSettings } from './fakeSettingsApi'
import { validatePortfolio, validateProfile, validateSchedule, validateTheme } from '../settings.validation'

describe('validateProfile', () => {
  it('accepts the default profile', () => {
    // Arrange
    const { profile } = createTestSettings()

    // Act
    const errors = validateProfile(profile)

    // Assert
    expect(errors).toEqual({})
  })

  it('requires a name and a valid WhatsApp number', () => {
    // Arrange
    const { profile } = createTestSettings()
    profile.name = '  '
    profile.whatsappNumber = '9999-8888'

    // Act
    const errors = validateProfile(profile)

    // Assert
    expect(errors.name?.key).toBe('required')
    expect(errors.whatsappNumber?.key).toBe('phone')
  })

  it('only accepts https social links', () => {
    // Arrange
    const { profile } = createTestSettings()
    profile.socials[0]!.url = 'http://instagram.com/rafa'
    profile.socials[1]!.url = ''

    // Act
    const errors = validateProfile(profile)

    // Assert
    expect(errors['socials.0']?.key).toBe('https')
    expect(errors['socials.1']).toBeUndefined()
  })
})

describe('validatePortfolio', () => {
  it('rejects more than 4 photos', () => {
    // Arrange
    const { portfolio } = createTestSettings()
    portfolio.photos = [5, 6, 7, 8, 9].map((imageId) => ({ imageId, url: `https://cdn.test/${imageId}.webp`, alt: 'Foto' }))

    // Act
    const errors = validatePortfolio(portfolio)

    // Assert
    expect(errors.photos).toEqual({ key: 'tooManyPhotos', params: { max: 4 } })
  })

  it('requires alt text on every photo', () => {
    // Arrange
    const { portfolio } = createTestSettings()
    portfolio.photos[0]!.alt = ''

    // Act
    const errors = validatePortfolio(portfolio)

    // Assert
    expect(errors['photos.0.alt']?.key).toBe('altRequired')
  })

  it('limits the story length', () => {
    // Arrange
    const { portfolio } = createTestSettings()
    portfolio.story = 'a'.repeat(1201)

    // Act
    const errors = validatePortfolio(portfolio)

    // Assert
    expect(errors.story).toEqual({ key: 'tooLong', params: { max: 1200 } })
  })
})

describe('validateSchedule', () => {
  it('accepts the default schedule', () => {
    // Arrange
    const { schedule } = createTestSettings()

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors).toEqual({})
  })

  it('accepts a service without cost and rejects a cost equal to or above the price', () => {
    // Arrange
    const { schedule } = createTestSettings()
    schedule.services = [
      { id: 'a', name: 'Corte', durationMinutes: 45, priceInCents: 5000 },
      { id: 'b', name: 'Barba', durationMinutes: 30, priceInCents: 4000, costInCents: 4000 },
      { id: 'c', name: 'Pezinho', durationMinutes: 15, priceInCents: 2000, costInCents: -1 },
    ]

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['services.0.costInCents']).toBeUndefined()
    expect(errors['services.1.costInCents']).toEqual({ key: 'serviceCost' })
    expect(errors['services.2.costInCents']).toEqual({ key: 'serviceCost' })
  })

  it('requires at least one service', () => {
    // Arrange
    const { schedule } = createTestSettings()
    schedule.services = []

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors.services?.key).toBe('minServices')
  })

  it('rejects a duration that is not a multiple of 5 and a zero price', () => {
    // Arrange
    const { schedule } = createTestSettings()
    schedule.services[0]!.durationMinutes = 42
    schedule.services[0]!.priceInCents = 0

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['services.0.durationMinutes']?.key).toBe('duration')
    expect(errors['services.0.priceInCents']?.key).toBe('price')
  })

  it('rejects an open day that closes before it opens', () => {
    // Arrange
    const { schedule } = createTestSettings()
    schedule.weeklyHours[1] = { weekday: 1, isOpen: true, opensAt: '18:00', closesAt: '09:00' }

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['weeklyHours.1']?.key).toBe('hoursOrder')
  })

  it('ignores the hours of a closed day', () => {
    // Arrange
    const { schedule } = createTestSettings()
    schedule.weeklyHours[0] = { weekday: 0, isOpen: false, opensAt: '18:00', closesAt: '09:00' }

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['weeklyHours.0']).toBeUndefined()
  })

  it('rejects a vacation that ends before it starts', () => {
    // Arrange
    const { schedule } = createTestSettings()
    schedule.vacation = { ...schedule.vacation, isEnabled: true, startsOn: '2026-12-20', endsOn: '2026-12-10' }

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['vacation.endsOn']?.key).toBe('vacationOrder')
  })
})

describe('validateTheme', () => {
  it('rejects a custom accent that is not a hex color', () => {
    // Arrange
    const { theme } = createTestSettings()
    theme.customAccent = 'gold'

    // Act
    const errors = validateTheme(theme)

    // Assert
    expect(errors.customAccent?.key).toBe('hexColor')
  })

  it('rejects an unknown button shape', () => {
    // Arrange
    const { theme } = createTestSettings()
    ;(theme as { buttonShape: string }).buttonShape = 'hexagon'

    // Act
    const errors = validateTheme(theme)

    // Assert
    expect(errors.buttonShape?.key).toBe('invalidOption')
  })

  it('rejects an unknown button style', () => {
    // Arrange
    const { theme } = createTestSettings()
    ;(theme as { buttonStyle: string }).buttonStyle = 'neon'

    // Act
    const errors = validateTheme(theme)

    // Assert
    expect(errors.buttonStyle?.key).toBe('invalidOption')
  })
})
