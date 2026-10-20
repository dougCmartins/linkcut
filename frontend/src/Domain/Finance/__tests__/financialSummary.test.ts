import { describe, expect, it } from 'vitest'
import { computeFinancialSummary } from '../financialSummary'

describe('computeFinancialSummary', () => {
  it('computes profit and margin from revenue and expenses', () => {
    // Arrange
    const revenue = 1_000_000
    const expenses = 600_000

    // Act
    const summary = computeFinancialSummary(revenue, expenses)

    // Assert
    expect(summary.profitInCents).toBe(400_000)
    expect(summary.marginPercent).toBe(40)
    expect(summary.isBelowHealthyMargin).toBe(false)
  })

  it('flags a margin below the 25% healthy threshold', () => {
    // Arrange
    const revenue = 1_000_000
    const expenses = 800_000

    // Act
    const summary = computeFinancialSummary(revenue, expenses)

    // Assert
    expect(summary.marginPercent).toBe(20)
    expect(summary.isBelowHealthyMargin).toBe(true)
  })

  it('returns a zero margin when there is no revenue', () => {
    // Arrange
    const revenue = 0
    const expenses = 50_000

    // Act
    const summary = computeFinancialSummary(revenue, expenses)

    // Assert
    expect(summary.marginPercent).toBe(0)
    expect(summary.profitInCents).toBe(-50_000)
  })
})
