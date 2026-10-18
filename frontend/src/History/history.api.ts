import type { CompletedService, DateRange, MonthFinances } from './history.model'
import { monthToDate } from './historyPeriod'
import { mockCompletedServices, mockMonthToDateExpenses } from '@/mocks/history'

/**
 * History data. MOCK until the backend exists: only these functions change
 * when they become HTTP calls (e.g. GET /history?from=&to=).
 */

const LATENCY_MS = import.meta.env.MODE === 'test' ? 0 : 250
const wait = () => new Promise((resolve) => setTimeout(resolve, LATENCY_MS))

/** Finished appointments inside the range (inclusive), newest first. */
export async function fetchCompletedServices(range: DateRange, now = new Date()): Promise<CompletedService[]> {
  await wait()
  return mockCompletedServices(now).filter((record) => record.date >= range.from && record.date <= range.to)
}

/** Revenue and expenses of the current month until now (what the dashboard shows). */
export async function fetchMonthToDateFinances(now = new Date()): Promise<MonthFinances> {
  const records = await fetchCompletedServices(monthToDate(now), now)
  return {
    revenueInCents: records.reduce((sum, record) => sum + record.priceInCents, 0),
    expensesInCents: mockMonthToDateExpenses(now),
  }
}
