import { afterEach, describe, expect, it, vi } from 'vitest'
import { fetchHistoryPage, requestHistoryReport } from '../history.api'
import { installFakeFinanceApi } from '../../__tests__/fakeFinanceApi'

const OCTOBER = { from: '2026-10-01', to: '2026-10-17' }

describe('history api', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('asks for the first rows of the period with the totals, then the next ones by cursor', async () => {
    // Arrange
    const { calls } = installFakeFinanceApi()

    // Act
    const first = await fetchHistoryPage(OCTOBER)
    const next = await fetchHistoryPage(OCTOBER, first.nextCursor)

    // Assert
    expect(calls[0]?.path).toBe('/finance/history?from=2026-10-01&to=2026-10-17')
    expect(first.items).toHaveLength(50)
    expect(first.items[0]).toEqual({
      id: expect.any(Number),
      date: '2026-10-17',
      time: '13:00',
      clientName: expect.any(String),
      serviceName: 'Corte + Barba',
      priceInCents: 8000,
      costInCents: 1000,
    })
    expect(first.summary).toEqual({ totalInCents: 17 * 17_000, count: 51, averageTicketInCents: 5667 })
    expect(calls[1]?.path).toBe(`/finance/history?from=2026-10-01&to=2026-10-17&cursor=${first.nextCursor}`)
    expect(next.items).toHaveLength(1)
    expect(next.summary).toBeNull()
    expect(next.nextCursor).toBeNull()
  })

  it('asks the API to e-mail the CSV of the period', async () => {
    // Arrange
    const { calls } = installFakeFinanceApi()

    // Act
    const answer = await requestHistoryReport(OCTOBER)

    // Assert
    expect(calls[0]).toMatchObject({ method: 'POST', path: '/finance/history/report', body: OCTOBER })
    expect(answer.email).toBe('ra****@linkcut.app')
  })
})
