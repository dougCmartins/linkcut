import { describe, expect, it } from 'vitest'
import { groupByDay, pageBounds } from '../historySummary'
import type { CompletedService } from '../history.model'

function record(id: number, date: string, priceInCents: number): CompletedService {
  return { id, date, time: '10:00', clientName: 'Cliente', serviceName: 'Corte', priceInCents, costInCents: 0 }
}

describe('pageBounds', () => {
  it('gives the positions of the first and last item shown', () => {
    // Arrange & Act
    const bounds = pageBounds(3, 50, 20)

    // Assert
    expect(bounds).toEqual({ firstItem: 101, lastItem: 120 })
  })

  it('is zero for an empty page', () => {
    // Arrange & Act
    const bounds = pageBounds(1, 50, 0)

    // Assert
    expect(bounds).toEqual({ firstItem: 0, lastItem: 0 })
  })
})

describe('groupByDay', () => {
  it('groups consecutive records of the same day with the day total', () => {
    // Arrange
    const records = [record(1, '2026-10-17', 5000), record(2, '2026-10-17', 4000), record(3, '2026-10-16', 8000)]

    // Act
    const groups = groupByDay(records)

    // Assert
    expect(groups.map((group) => [group.date, group.records.length, group.totalInCents])).toEqual([
      ['2026-10-17', 2, 9000],
      ['2026-10-16', 1, 8000],
    ])
  })
})
