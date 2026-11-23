import { describe, expect, it } from 'vitest'
import { installFakeApi } from '@/utils/testFetch'
import { createRule, fetchReport, fetchRules, requestReportCsv, simulate } from '../commission.api'
import { formatPercent, monthBounds, parsePercentInput } from '../commissionFormat'

describe('commission api', () => {
  it('maps rules from snake_case', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /finance/commission/rules': () => ({
        status: 200,
        body: [{ id: 1, service_id: 4, staff_id: null, percent: 500, starts_on: '2026-11-01' }],
      }),
    })

    // Act
    const rules = await fetchRules()

    // Assert
    expect(calls[0]?.path).toBe('/finance/commission/rules')
    expect(rules).toEqual([{ id: 1, serviceId: 4, staffId: null, percent: 500, startsOn: '2026-11-01' }])
  })

  it('sends a new rule in snake_case, percent in tenths', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'POST /finance/commission/rules': () => ({
        status: 201,
        body: { id: 2, service_id: 4, staff_id: 9, percent: 600, starts_on: '2026-11-01' },
      }),
    })

    // Act
    const rule = await createRule({ serviceId: 4, staffId: 9, percent: 600, startsOn: '2026-11-01' })

    // Assert
    expect(calls[0]?.body).toEqual({ service_id: 4, staff_id: 9, percent: 600, starts_on: '2026-11-01' })
    expect(rule.staffId).toBe(9)
  })

  it('maps the simulation result', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'POST /finance/commission/simulate': () => ({
        status: 200,
        body: {
          base_in_cents: 10000,
          percent: 500,
          amount_in_cents: 4600,
          adjustments: [{ name: 'Taxa', stage: 'after', kind: 'percent', applies_to: 'price', amount_in_cents: 400 }],
        },
      }),
    })

    // Act
    const result = await simulate({ priceInCents: 10000, percent: 500, costInCents: 0, staffId: 1, useSavedAdjustments: true })

    // Assert
    expect(calls[0]?.body).toMatchObject({ price_in_cents: 10000, use_saved_adjustments: true })
    expect(result.amountInCents).toBe(4600)
    expect(result.adjustments[0]).toMatchObject({ name: 'Taxa', amountInCents: 400 })
  })

  it('asks the report with the barber filter only when given', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /finance/commission/report': () => ({
        status: 200,
        body: { from: '2026-11-01', to: '2026-11-30', total_amount_in_cents: 0, entries: [] },
      }),
      'POST /finance/commission/report': () => ({ status: 202, body: { email: 'o***@example.com' } }),
    })

    // Act
    await fetchReport({ from: '2026-11-01', to: '2026-11-30' })
    await fetchReport({ from: '2026-11-01', to: '2026-11-30' }, 5)
    const exported = await requestReportCsv({ from: '2026-11-01', to: '2026-11-30' }, 5)

    // Assert
    expect(calls[0]?.query.has('staff_id')).toBe(false)
    expect(calls[1]?.query.get('staff_id')).toBe('5')
    expect(calls[2]?.body).toEqual({ from: '2026-11-01', to: '2026-11-30', staff_id: 5 })
    expect(exported.email).toBe('o***@example.com')
  })
})

describe('commission format', () => {
  it('converts percent text to tenths and back', () => {
    // Arrange
    const inputs = ['50', '7,5', '7.5', ' 100 % ', '', '101', 'abc']

    // Act
    const parsed = inputs.map((text) => parsePercentInput(text))

    // Assert
    expect(parsed).toEqual([500, 75, 75, 1000, null, null, null])
    expect(formatPercent(75)).toBe('7,5%')
    expect(formatPercent(500)).toBe('50%')
  })

  it('finds the first and last day of the month', () => {
    // Arrange
    const date = new Date(2026, 1, 10)

    // Act
    const bounds = monthBounds(date)

    // Assert
    expect(bounds).toEqual({ from: '2026-02-01', to: '2026-02-28' })
  })
})
