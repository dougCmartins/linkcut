import { describe, expect, it } from 'vitest'
import { customRangeError, monthToDate, periodRange, startOfWeek } from '../historyPeriod'
import { HistoryPeriod } from '../history.model'
import { toIsoDate } from '@/utils/date'

const SATURDAY_OCT_17 = new Date(2026, 9, 17) // Saturday, 17 Oct 2026
const noCustom = { from: '', to: '' }

describe('startOfWeek', () => {
  it('goes back to Monday', () => {
    // Arrange
    const saturday = SATURDAY_OCT_17

    // Act
    const monday = startOfWeek(saturday)

    // Assert
    expect(toIsoDate(monday)).toBe('2026-10-12')
  })

  it('treats Sunday as the last day of the week', () => {
    // Arrange
    const sunday = new Date(2026, 9, 18)

    // Act
    const monday = startOfWeek(sunday)

    // Assert
    expect(toIsoDate(monday)).toBe('2026-10-12')
  })
})

describe('periodRange', () => {
  it('builds week, month and year ranges until today', () => {
    // Arrange
    const today = SATURDAY_OCT_17

    // Act
    const ranges = [HistoryPeriod.Week, HistoryPeriod.Month, HistoryPeriod.Year].map((period) =>
      periodRange(period, today, noCustom),
    )

    // Assert
    expect(ranges).toEqual([
      { from: '2026-10-12', to: '2026-10-17' },
      { from: '2026-10-01', to: '2026-10-17' },
      { from: '2026-01-01', to: '2026-10-17' },
    ])
  })

  it('uses the dates typed for the custom period', () => {
    // Arrange
    const custom = { from: '2025-03-01', to: '2025-03-31' }

    // Act
    const range = periodRange(HistoryPeriod.Custom, SATURDAY_OCT_17, custom)

    // Assert
    expect(range).toEqual(custom)
  })

  it('gives the dashboard range: day 1 of the month until today', () => {
    // Arrange & Act
    const range = monthToDate(SATURDAY_OCT_17)

    // Assert
    expect(range).toEqual({ from: '2026-10-01', to: '2026-10-17' })
  })
})

describe('customRangeError', () => {
  it('accepts a range of a single day', () => {
    // Arrange
    const range = { from: '2026-10-10', to: '2026-10-10' }

    // Act
    const error = customRangeError(range)

    // Assert
    expect(error).toBeNull()
  })

  it('rejects an end date before the start date', () => {
    // Arrange
    const range = { from: '2026-10-10', to: '2026-10-01' }

    // Act
    const error = customRangeError(range)

    // Assert
    expect(error).toBe('rangeOrder')
  })

  it('asks for the missing start date', () => {
    // Arrange
    const range = { from: '', to: '2026-10-01' }

    // Act
    const error = customRangeError(range)

    // Assert
    expect(error).toBe('fromRequired')
  })
})
