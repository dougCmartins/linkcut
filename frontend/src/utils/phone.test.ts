import { describe, expect, it } from 'vitest'
import { isValidBrazilianPhone } from './phone'

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
