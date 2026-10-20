import { describe, expect, it } from 'vitest'
import { dueDayInMonth, isDue } from '../expenseCalculation'

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
