import { describe, expect, it } from 'vitest'
import { installFakeApi } from '@/utils/testFetch'
import { fetchBrandReport, requestBrandReportCsv } from '../brandReport.api'

const totals = { revenue_in_cents: 16000, count: 3, average_ticket_in_cents: 5333, commission_in_cents: 8000 }

describe('brand report api', () => {
  it('maps the total and the units from snake_case', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /finance/brand/report': () => ({
        status: 200,
        body: {
          from: '2026-10-01',
          to: '2026-10-31',
          total: totals,
          units: [{ unit_id: 10, name: 'Matriz', slug: 'marca', is_main: true, totals }],
        },
      }),
    })

    // Act
    const report = await fetchBrandReport({ from: '2026-10-01', to: '2026-10-31' })

    // Assert
    expect(calls[0]?.query.get('from')).toBe('2026-10-01')
    expect(report.total).toEqual({ revenueInCents: 16000, count: 3, averageTicketInCents: 5333, commissionInCents: 8000 })
    expect(report.units[0]).toMatchObject({ unitId: 10, name: 'Matriz', isMain: true })
    expect(report.units[0]?.totals.commissionInCents).toBe(8000)
  })

  it('asks the CSV by e-mail for the period', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'POST /finance/brand/report': () => ({ status: 202, body: { email: 'o***@example.com' } }),
    })

    // Act
    const result = await requestBrandReportCsv({ from: '2026-10-01', to: '2026-10-31' })

    // Assert
    expect(calls[0]?.body).toEqual({ from: '2026-10-01', to: '2026-10-31' })
    expect(result.email).toBe('o***@example.com')
  })
})
