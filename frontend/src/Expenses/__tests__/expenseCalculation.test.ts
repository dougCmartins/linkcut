import { describe, expect, it } from 'vitest'
import { dueDayInMonth, isDue, monthToDateExpenses } from '../expenseCalculation'
import type { ExpenseList } from '../expenses.model'

const NOW = new Date(2026, 9, 17, 15, 0) // 17 Oct 2026

const expenses: ExpenseList = {
  fixed: [
    { id: 'rent', name: 'Aluguel', amountInCents: 150_000, dueDay: 5 },
    { id: 'tax', name: 'DAS', amountInCents: 7_590, dueDay: 20 },
  ],
  oneOff: [
    { id: 'blades', description: 'Lâminas', amountInCents: 21_000, date: '2026-10-02' },
    { id: 'old', description: 'Mês passado', amountInCents: 9_999, date: '2026-09-28' },
    { id: 'today', description: 'Hoje', amountInCents: 1_000, date: '2026-10-17' },
  ],
}

describe('fixed expense due day', () => {
  it('counts from the due day on (cash basis)', () => {
    // Arrange & Act
    const [beforeDue, onDue, afterDue] = [4, 5, 6].map((day) => isDue({ dueDay: 5 }, new Date(2026, 9, day)))

    // Assert
    expect([beforeDue, onDue, afterDue]).toEqual([false, true, true])
  })

  it('falls on the last day in shorter months', () => {
    // Arrange & Act
    const days = [dueDayInMonth({ dueDay: 31 }, new Date(2026, 1, 10)), dueDayInMonth({ dueDay: 31 }, new Date(2026, 3, 10))]

    // Assert
    expect(days).toEqual([28, 30])
    expect(isDue({ dueDay: 31 }, new Date(2026, 3, 30))).toBe(true)
  })
})

describe('monthToDateExpenses', () => {
  it('adds fixed already due, one-off of the month and the cost of the appointments', () => {
    // Arrange
    const serviceCostsInCents = 4_200

    // Act
    const breakdown = monthToDateExpenses(expenses, serviceCostsInCents, NOW)

    // Assert
    expect(breakdown).toEqual({
      fixedInCents: 150_000,
      oneOffInCents: 22_000,
      serviceCostsInCents: 4_200,
      totalInCents: 176_200,
    })
  })

  it('is zero with nothing registered', () => {
    // Arrange & Act
    const breakdown = monthToDateExpenses({ fixed: [], oneOff: [] }, 0, NOW)

    // Assert
    expect(breakdown.totalInCents).toBe(0)
  })
})
