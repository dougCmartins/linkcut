import { describe, expect, it } from 'vitest'
import { codeSchema, credentialsErrors } from '../auth.schemas'

describe('credentialsErrors', () => {
  it('accepts a valid e-mail and password', () => {
    // Arrange
    const input = { email: '  rafael@linkcut.app ', password: 'linkcut123' }

    // Act
    const errors = credentialsErrors(input)

    // Assert
    expect(errors).toEqual({})
  })

  it('flags an empty e-mail and a short password', () => {
    // Arrange
    const input = { email: '', password: '123' }

    // Act
    const errors = credentialsErrors(input)

    // Assert
    expect(errors).toEqual({ email: 'emailRequired', password: 'passwordTooShort' })
  })

  it('flags a malformed e-mail', () => {
    // Arrange
    const input = { email: 'rafael@', password: 'linkcut123' }

    // Act
    const errors = credentialsErrors(input)

    // Assert
    expect(errors.email).toBe('emailInvalid')
  })
})

describe('codeSchema', () => {
  it('only accepts exactly 6 digits', () => {
    // Arrange
    const samples = ['123456', '12345', '1234567', '12a456']

    // Act
    const results = samples.map((sample) => codeSchema.safeParse(sample).success)

    // Assert
    expect(results).toEqual([true, false, false, false])
  })
})
