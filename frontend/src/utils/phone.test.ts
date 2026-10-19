import { describe, expect, it } from 'vitest'
import { buildWhatsappUrl, isValidBrazilianPhone, isValidWhatsappNumber, toWhatsappNumber } from './phone'

describe('isValidBrazilianPhone', () => {
  it('accepts a formatted mobile number', () => {
    // Arrange
    const phone = '(11) 91234-5678'

    // Act
    const isValid = isValidBrazilianPhone(phone)

    // Assert
    expect(isValid).toBe(true)
  })

  it('rejects a number without area code', () => {
    // Arrange
    const phone = '91234-5678'

    // Act
    const isValid = isValidBrazilianPhone(phone)

    // Assert
    expect(isValid).toBe(false)
  })
})

describe('isValidWhatsappNumber', () => {
  it('accepts a number that already has the 55 country code', () => {
    // Arrange
    const phone = '+55 (11) 99999-8888'

    // Act
    const isValid = isValidWhatsappNumber(phone)

    // Assert
    expect(isValid).toBe(true)
  })

  it('rejects a number with too many digits', () => {
    // Arrange
    const phone = '5511999998888777'

    // Act
    const isValid = isValidWhatsappNumber(phone)

    // Assert
    expect(isValid).toBe(false)
  })
})

describe('toWhatsappNumber', () => {
  it('adds the country code when missing', () => {
    // Arrange
    const phone = '(11) 99999-8888'

    // Act
    const number = toWhatsappNumber(phone)

    // Assert
    expect(number).toBe('5511999998888')
  })

  it('keeps a number that already has the country code', () => {
    // Arrange
    const phone = '5511999998888'

    // Act
    const number = toWhatsappNumber(phone)

    // Assert
    expect(number).toBe('5511999998888')
  })
})

describe('buildWhatsappUrl', () => {
  it('encodes the default message', () => {
    // Arrange
    const phone = '11999998888'
    const message = 'Olá! Tudo bem?'

    // Act
    const url = buildWhatsappUrl(phone, message)

    // Assert
    expect(url).toBe('https://wa.me/5511999998888?text=Ol%C3%A1!%20Tudo%20bem%3F')
  })

  it('omits the text parameter when the message is empty', () => {
    // Arrange
    const phone = '11999998888'

    // Act
    const url = buildWhatsappUrl(phone, '   ')

    // Assert
    expect(url).toBe('https://wa.me/5511999998888')
  })
})
