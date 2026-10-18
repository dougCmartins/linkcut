import { describe, expect, it } from 'vitest'
import { formatWeekdayShort, fromIsoDate, nextDays, toIsoDate } from './date'

describe('nextDays', () => {
  it('returns consecutive days starting today at midnight', () => {
    // Arrange
    const from = new Date(2026, 9, 30, 15, 45)

    // Act
    const days = nextDays(3, from)

    // Assert
    expect(days.map(toIsoDate)).toEqual(['2026-10-30', '2026-10-31', '2026-11-01'])
    expect(days[0]?.getHours()).toBe(0)
  })
})

describe('toIsoDate / fromIsoDate', () => {
  it('round-trips a local date without timezone shift', () => {
    // Arrange
    const isoDate = '2026-01-05'

    // Act
    const result = toIsoDate(fromIsoDate(isoDate))

    // Assert
    expect(result).toBe(isoDate)
  })
})

describe('formatWeekdayShort', () => {
  it('formats the weekday in Portuguese without the trailing dot', () => {
    // Arrange
    const monday = new Date(2026, 9, 19)

    // Act
    const weekday = formatWeekdayShort(monday)

    // Assert
    expect(weekday).toBe('seg')
  })
})
