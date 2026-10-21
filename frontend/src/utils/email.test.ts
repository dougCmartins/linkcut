import { describe, expect, it } from 'vitest'
import { isValidEmail } from './email'

describe('isValidEmail', () => {
  it('accepts common e-mails, with spaces around', () => {
    // Arrange & Act & Assert
    expect(isValidEmail('ana@example.com')).toBe(true)
    expect(isValidEmail(' joao.silva+cortes@barbearia.com.br ')).toBe(true)
  })

  it('refuses incomplete ones', () => {
    // Arrange & Act & Assert
    for (const value of ['', 'ana', 'ana@', 'ana@example', '@example.com', 'ana @example.com', 'ana@example.c']) {
      expect(isValidEmail(value)).toBe(false)
    }
  })
})
