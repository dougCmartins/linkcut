import { beforeEach, describe, expect, it } from 'vitest'
import { fetchMonthToDateFinances } from '../dashboard.api'
import { fetchCompletedServices, recordCompletedService } from '@/History/history.api'
import { addOneOffExpense, fetchExpenses } from '@/Expenses/expenses.api'
import { monthToDateExpenses } from '@/Expenses/expenseCalculation'

const NOW = new Date(2026, 9, 17, 15, 0) // Saturday 17 Oct 2026, 15:00
const monthRange = { from: '2026-10-01', to: '2026-10-17' }

describe('fetchMonthToDateFinances', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('revenue is the history total of the month; expenses add the cost of each appointment', async () => {
    // Arrange
    const records = await fetchCompletedServices(monthRange, NOW)
    const serviceCosts = records.reduce((sum, record) => sum + (record.costInCents ?? 0), 0)
    const expected = monthToDateExpenses(await fetchExpenses(NOW), serviceCosts, NOW)

    // Act
    const finances = await fetchMonthToDateFinances(NOW)

    // Assert
    expect(finances.revenueInCents).toBe(records.reduce((sum, record) => sum + record.priceInCents, 0))
    expect(serviceCosts).toBeGreaterThan(0)
    expect(finances.expenses).toEqual(expected)
  })

  it('a concluded appointment adds its price to revenue and its cost to expenses', async () => {
    // Arrange
    const before = await fetchMonthToDateFinances(NOW)

    // Act
    await recordCompletedService({
      id: 'req-77', date: '2026-10-17', time: '08:15', clientName: 'Ana', serviceName: 'Corte', priceInCents: 5000, costInCents: 600,
    })

    // Assert
    const after = await fetchMonthToDateFinances(NOW)
    expect(after.revenueInCents).toBe(before.revenueInCents + 5000)
    expect(after.expenses.serviceCostsInCents).toBe(before.expenses.serviceCostsInCents + 600)
  })

  it('a new one-off expense of the month goes into the total', async () => {
    // Arrange
    const before = await fetchMonthToDateFinances(NOW)

    // Act
    await addOneOffExpense({ description: 'Pomadas', amountInCents: 9_000, date: '2026-10-10' }, NOW)

    // Assert
    const after = await fetchMonthToDateFinances(NOW)
    expect(after.expenses.oneOffInCents).toBe(before.expenses.oneOffInCents + 9_000)
    expect(after.expenses.totalInCents).toBe(before.expenses.totalInCents + 9_000)
  })
})
