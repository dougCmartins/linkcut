import type { ExpenseBreakdown, ExpenseList, FixedExpense } from './expenses.model'
import { toIsoDate } from '@/utils/date'

/** Day a fixed expense falls on in the month of `date` (31 becomes 30 in a 30-day month). */
export function dueDayInMonth(expense: Pick<FixedExpense, 'dueDay'>, date: Date): number {
  const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()
  return Math.min(expense.dueDay, lastDay)
}

/** A fixed expense counts in full from its due day on (cash basis). */
export function isDue(expense: Pick<FixedExpense, 'dueDay'>, now: Date): boolean {
  return dueDayInMonth(expense, now) <= now.getDate()
}

/**
 * Expenses of the current month, from day 1 until `now`:
 * fixed ones already due + one-off ones of the month + cost of the concluded appointments.
 */
export function monthToDateExpenses(expenses: ExpenseList, serviceCostsInCents: number, now: Date): ExpenseBreakdown {
  const today = toIsoDate(now)
  const monthStart = today.slice(0, 8) + '01'
  const fixedInCents = expenses.fixed
    .filter((expense) => isDue(expense, now))
    .reduce((sum, expense) => sum + expense.amountInCents, 0)
  const oneOffInCents = expenses.oneOff
    .filter((expense) => expense.date >= monthStart && expense.date <= today)
    .reduce((sum, expense) => sum + expense.amountInCents, 0)

  return {
    fixedInCents,
    oneOffInCents,
    serviceCostsInCents,
    totalInCents: fixedInCents + oneOffInCents + serviceCostsInCents,
  }
}
