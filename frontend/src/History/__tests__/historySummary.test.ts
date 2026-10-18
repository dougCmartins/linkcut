import { describe, expect, it } from 'vitest'
import { groupByDay, paginate, summarize } from '../historySummary'
import type { CompletedService } from '../history.model'

function record(id: string, date: string, priceInCents: number): CompletedService {
  return { id, date, time: '10:00', clientName: 'Cliente', serviceName: 'Corte', priceInCents }
}

describe('summarize', () => {
  it('adds the total, counts and computes the average ticket', () => {
    // Arrange
    const records = [record('1', '2026-10-17', 5000), record('2', '2026-10-17', 4000), record('3', '2026-10-16', 8000)]

    // Act
    const summary = summarize(records)

    // Assert
    expect(summary).toEqual({ totalInCents: 17000, count: 3, averageTicketInCents: 5667 })
  })

  it('returns zeros for an empty period', () => {
    // Arrange & Act
    const summary = summarize([])

    // Assert
    expect(summary).toEqual({ totalInCents: 0, count: 0, averageTicketInCents: 0 })
  })
})

describe('paginate', () => {
  const items = Array.from({ length: 120 }, (_, index) => index + 1)

  it('returns the requested page and the visible positions', () => {
    // Arrange
    const pageSize = 50

    // Act
    const page = paginate(items, 3, pageSize)

    // Assert
    expect(page.items).toHaveLength(20)
    expect(page).toMatchObject({ page: 3, totalPages: 3, firstItem: 101, lastItem: 120 })
  })

  it('clamps a page beyond the end to the last page', () => {
    // Arrange
    const pageSize = 100

    // Act
    const page = paginate(items, 9, pageSize)

    // Assert
    expect(page).toMatchObject({ page: 2, firstItem: 101, lastItem: 120 })
  })

  it('handles an empty list', () => {
    // Arrange & Act
    const page = paginate([], 1, 50)

    // Assert
    expect(page).toMatchObject({ items: [], page: 1, totalPages: 1, firstItem: 0, lastItem: 0 })
  })
})

describe('groupByDay', () => {
  it('groups consecutive records of the same day with the day total', () => {
    // Arrange
    const records = [record('1', '2026-10-17', 5000), record('2', '2026-10-17', 4000), record('3', '2026-10-16', 8000)]

    // Act
    const groups = groupByDay(records)

    // Assert
    expect(groups.map((group) => [group.date, group.records.length, group.totalInCents])).toEqual([
      ['2026-10-17', 2, 9000],
      ['2026-10-16', 1, 8000],
    ])
  })
})
