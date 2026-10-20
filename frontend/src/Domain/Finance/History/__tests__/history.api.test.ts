import { afterEach, describe, expect, it, vi } from 'vitest'
import { downloadHistoryCsv, fetchHistoryPage } from '../history.api'
import { historyFileName } from '../historyExport'
import { installFakeFinanceApi } from '../../__tests__/fakeFinanceApi'

const OCTOBER = { from: '2026-10-01', to: '2026-10-17' }

describe('history api', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('asks the API for one page of the period and maps it', async () => {
    // Arrange
    const { calls } = installFakeFinanceApi()

    // Act
    const page = await fetchHistoryPage(OCTOBER, 1, 50)

    // Assert
    expect(calls[0]?.path).toBe('/finance/history?from=2026-10-01&to=2026-10-17&page=1&per_page=50')
    expect(page.items).toHaveLength(50)
    expect(page.items[0]).toEqual({
      id: expect.any(Number),
      date: '2026-10-17',
      time: '13:00',
      clientName: expect.any(String),
      serviceName: 'Corte + Barba',
      priceInCents: 8000,
      costInCents: 1000,
    })
    expect(page.summary).toEqual({ totalInCents: 17 * 17_000, count: 51, averageTicketInCents: 5667 })
    expect(page.lastPage).toBe(2)
  })

  it('downloads the CSV built by the API, with the BOM for Excel', async () => {
    // Arrange
    const { calls } = installFakeFinanceApi()

    // Act
    const csv = await downloadHistoryCsv(OCTOBER)

    // Assert
    expect(calls[0]?.path).toBe('/finance/history/export?from=2026-10-01&to=2026-10-17')
    const bytes = new Uint8Array(await csv.arrayBuffer())
    expect([...bytes.slice(0, 3)]).toEqual([0xef, 0xbb, 0xbf])
    expect(historyFileName(OCTOBER)).toBe('historico-linkcut_2026-10-01_a_2026-10-17.csv')
  })
})
