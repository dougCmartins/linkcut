import { describe, expect, it } from 'vitest'
import { formatBRL, formatMoneyInput, parseMoneyInput } from './currency'

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

describe('parseMoneyInput', () => {
  it.each([
    ['50', 5000],
    ['50,5', 5050],
    ['50.5', 5050],
    ['1.500,00', 150000],
    ['R$ 75,90', 7590],
  ])('reads "%s" as %i cents', (text, expected) => {
    // Arrange & Act
    const cents = parseMoneyInput(text)

    // Assert
    expect(cents).toBe(expected)
  })

  it('returns null for empty or invalid text', () => {
    // Arrange & Act
    const results = [parseMoneyInput(''), parseMoneyInput('  '), parseMoneyInput('abc')]

    // Assert
    expect(results).toEqual([null, null, null])
  })
})

describe('formatMoneyInput', () => {
  it('writes cents with a decimal comma and leaves zero empty', () => {
    // Arrange & Act
    const results = [formatMoneyInput(5050), formatMoneyInput(0)]

    // Assert
    expect(results).toEqual(['50,50', ''])
  })
})
