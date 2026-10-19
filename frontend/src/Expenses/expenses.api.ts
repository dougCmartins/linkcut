import type { ExpenseList, FixedExpense, FixedExpenseInput, OneOffExpense, OneOffExpenseInput } from './expenses.model'
import { mockReadExpenses, mockWriteExpenses } from '@/mocks/expenses'

/**
 * Barber expenses. MOCK until the backend exists (localStorage): only these
 * functions change when they become HTTP calls (e.g. GET/POST/DELETE /expenses).
 */

const LATENCY_MS = import.meta.env.MODE === 'test' ? 0 : 200
const wait = () => new Promise((resolve) => setTimeout(resolve, LATENCY_MS))
const newId = (prefix: string) => `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`

export async function fetchExpenses(now = new Date()): Promise<ExpenseList> {
  await wait()
  return mockReadExpenses(now)
}

export async function addFixedExpense(input: FixedExpenseInput, now = new Date()): Promise<FixedExpense> {
  await wait()
  const expense: FixedExpense = { ...input, id: newId('fixed') }
  const current = mockReadExpenses(now)
  mockWriteExpenses({ ...current, fixed: [...current.fixed, expense] })
  return expense
}

export async function addOneOffExpense(input: OneOffExpenseInput, now = new Date()): Promise<OneOffExpense> {
  await wait()
  const expense: OneOffExpense = { ...input, id: newId('oneoff') }
  const current = mockReadExpenses(now)
  mockWriteExpenses({ ...current, oneOff: [...current.oneOff, expense] })
  return expense
}

/** Replaces an expense (same id) with its edited values. */
export async function updateExpense(expense: FixedExpense | OneOffExpense, now = new Date()): Promise<void> {
  await wait()
  const current = mockReadExpenses(now)
  mockWriteExpenses({
    fixed: current.fixed.map((item) => (item.id === expense.id && 'dueDay' in expense ? expense : item)),
    oneOff: current.oneOff.map((item) => (item.id === expense.id && 'date' in expense ? expense : item)),
  })
}

/** Removes a fixed or one-off expense by id. */
export async function removeExpense(id: string, now = new Date()): Promise<void> {
  await wait()
  const current = mockReadExpenses(now)
  mockWriteExpenses({
    fixed: current.fixed.filter((expense) => expense.id !== id),
    oneOff: current.oneOff.filter((expense) => expense.id !== id),
  })
}

/** Puts back an expense removed by mistake (undo), keeping its id. */
export async function restoreExpense(expense: FixedExpense | OneOffExpense, now = new Date()): Promise<void> {
  await wait()
  const current = mockReadExpenses(now)
  if ('dueDay' in expense) mockWriteExpenses({ ...current, fixed: [...current.fixed, expense] })
  else mockWriteExpenses({ ...current, oneOff: [...current.oneOff, expense] })
}
