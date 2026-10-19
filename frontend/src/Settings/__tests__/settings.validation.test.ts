import { describe, expect, it } from 'vitest'
import { createDefaultSettings } from '../settings.defaults'
import { validatePortfolio, validateProfile, validateSchedule, validateSecurity, validateTheme } from '../settings.validation'

describe('validateProfile', () => {
  it('accepts the default profile', () => {
    // Arrange
    const { profile } = createDefaultSettings()

    // Act
    const errors = validateProfile(profile)

    // Assert
    expect(errors).toEqual({})
  })

  it('requires a name and a valid WhatsApp number', () => {
    // Arrange
    const { profile } = createDefaultSettings()
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
    const { profile } = createDefaultSettings()
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
    const { portfolio } = createDefaultSettings()
    portfolio.photos.push({ id: 'extra', src: 'https://example.com/5.jpg', alt: 'Quinta foto' })

    // Act
    const errors = validatePortfolio(portfolio)

    // Assert
    expect(errors.photos).toEqual({ key: 'tooManyPhotos', params: { max: 4 } })
  })

  it('requires alt text on every photo', () => {
    // Arrange
    const { portfolio } = createDefaultSettings()
    portfolio.photos[2]!.alt = ''

    // Act
    const errors = validatePortfolio(portfolio)

    // Assert
    expect(errors['photos.2.alt']?.key).toBe('altRequired')
  })

  it('limits the story length', () => {
    // Arrange
    const { portfolio } = createDefaultSettings()
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
    const { schedule } = createDefaultSettings()

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors).toEqual({})
  })

  it('accepts a service without cost and rejects a cost equal to or above the price', () => {
    // Arrange
    const { schedule } = createDefaultSettings()
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
    const { schedule } = createDefaultSettings()
    schedule.services = []

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors.services?.key).toBe('minServices')
  })

  it('rejects a duration that is not a multiple of 5 and a zero price', () => {
    // Arrange
    const { schedule } = createDefaultSettings()
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
    const { schedule } = createDefaultSettings()
    schedule.weeklyHours[1] = { weekday: 1, isOpen: true, opensAt: '18:00', closesAt: '09:00' }

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['weeklyHours.1']?.key).toBe('hoursOrder')
  })

  it('ignores the hours of a closed day', () => {
    // Arrange
    const { schedule } = createDefaultSettings()
    schedule.weeklyHours[0] = { weekday: 0, isOpen: false, opensAt: '18:00', closesAt: '09:00' }

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['weeklyHours.0']).toBeUndefined()
  })

  it('rejects a vacation that ends before it starts', () => {
    // Arrange
    const { schedule } = createDefaultSettings()
    schedule.vacation = { ...schedule.vacation, isEnabled: true, startsOn: '2026-12-20', endsOn: '2026-12-10' }

    // Act
    const errors = validateSchedule(schedule)

    // Assert
    expect(errors['vacation.endsOn']?.key).toBe('vacationOrder')
  })
})

describe('validateSecurity', () => {
  it('accepts asking every time or a known channel', () => {
    // Arrange
    const choices = [null, 'whatsapp', 'email'] as const

    // Act
    const results = choices.map((channel) => validateSecurity({ defaultTwoFactorChannel: channel }))

    // Assert
    results.forEach((errors) => expect(errors).toEqual({}))
  })

  it('rejects an unknown channel', () => {
    // Arrange
    const security = { defaultTwoFactorChannel: 'sms' } as unknown as Parameters<typeof validateSecurity>[0]

    // Act
    const errors = validateSecurity(security)

    // Assert
    expect(errors.defaultTwoFactorChannel?.key).toBe('invalidOption')
  })
})

describe('validateTheme', () => {
  it('rejects a custom accent that is not a hex color', () => {
    // Arrange
    const { theme } = createDefaultSettings()
    theme.customAccent = 'gold'

    // Act
    const errors = validateTheme(theme)

    // Assert
    expect(errors.customAccent?.key).toBe('hexColor')
  })

  it('rejects an unknown button shape', () => {
    // Arrange
    const { theme } = createDefaultSettings()
    ;(theme as { buttonShape: string }).buttonShape = 'hexagon'

    // Act
    const errors = validateTheme(theme)

    // Assert
    expect(errors.buttonShape?.key).toBe('invalidOption')
  })

  it('rejects an unknown button style', () => {
    // Arrange
    const { theme } = createDefaultSettings()
    ;(theme as { buttonStyle: string }).buttonStyle = 'neon'

    // Act
    const errors = validateTheme(theme)

    // Assert
    expect(errors.buttonStyle?.key).toBe('invalidOption')
  })
})
