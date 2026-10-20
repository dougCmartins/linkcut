import type { FixedExpense } from './expenses.model'

/** Day a fixed expense falls on in the month of `date` (31 becomes 30 in a 30-day month). Same rule as the API. */
export function dueDayInMonth(expense: Pick<FixedExpense, 'dueDay'>, date: Date): number {
  const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()
  return Math.min(expense.dueDay, lastDay)
}

/** A fixed expense counts in full from its due day on (cash basis). */
export function isDue(expense: Pick<FixedExpense, 'dueDay'>, now: Date): boolean {
  return dueDayInMonth(expense, now) <= now.getDate()
}
