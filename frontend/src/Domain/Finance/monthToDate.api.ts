import type { MonthFinances } from './financialSummary'
import { http } from '@/http/client'

interface MonthToDateBody {
  from: string
  to: string
  revenue_in_cents: number
  expenses: {
    fixed_in_cents: number
    one_off_in_cents: number
    service_costs_in_cents: number
    total_in_cents: number
  }
}

/** Revenue and expenses of the current month until today, in the barbershop's timezone (computed by the API). */
export async function fetchMonthToDateFinances(): Promise<MonthFinances> {
  const { data } = await http<MonthToDateBody>('/finance/month-to-date')
  return {
    revenueInCents: data.revenue_in_cents,
    expenses: {
      fixedInCents: data.expenses.fixed_in_cents,
      oneOffInCents: data.expenses.one_off_in_cents,
      serviceCostsInCents: data.expenses.service_costs_in_cents,
      totalInCents: data.expenses.total_in_cents,
    },
  }
}
