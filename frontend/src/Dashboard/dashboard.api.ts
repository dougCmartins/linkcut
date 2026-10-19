import type { MonthFinances } from './financialSummary'
import { fetchCompletedServices } from '@/History/history.api'
import { monthToDate } from '@/History/historyPeriod'
import { fetchExpenses } from '@/Expenses/expenses.api'
import { monthToDateExpenses } from '@/Expenses/expenseCalculation'

/**
 * Month-to-date finances: revenue of the concluded appointments, minus fixed,
 * one-off and per-procedure costs. Later a single backend call (GET /finances/month-to-date).
 */
export async function fetchMonthToDateFinances(now = new Date()): Promise<MonthFinances> {
  const [records, expenses] = await Promise.all([fetchCompletedServices(monthToDate(now), now), fetchExpenses(now)])
  const revenueInCents = records.reduce((sum, record) => sum + record.priceInCents, 0)
  const serviceCostsInCents = records.reduce((sum, record) => sum + (record.costInCents ?? 0), 0)
  return { revenueInCents, expenses: monthToDateExpenses(expenses, serviceCostsInCents, now) }
}
