import { describe, expect, it } from 'vitest'
import { formatBRL } from './currency'

describe('formatBRL', () => {
  it('formats cents as Brazilian Real', () => {
    // Arrange
    const amountInCents = 5000

    // Act
    const formatted = formatBRL(amountInCents)

    // Assert
    expect(formatted.replace(/\s/g, ' ')).toBe('R$ 50,00')
  })

  it('formats thousands with a dot separator', () => {
    // Arrange
    const amountInCents = 1234567

    // Act
    const formatted = formatBRL(amountInCents)

    // Assert
    expect(formatted.replace(/\s/g, ' ')).toBe('R$ 12.345,67')
  })
})
