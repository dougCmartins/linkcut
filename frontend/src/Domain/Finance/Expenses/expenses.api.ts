import type { ExpenseList, FixedExpense, FixedExpenseInput, OneOffExpense, OneOffExpenseInput } from './expenses.model'
import { http } from '@/http/client'

/** Expenses API (snake_case on the wire, camelCase in the SPA). Removals are soft: they can be restored. */

interface FixedExpenseBody {
  id: number
  name: string
  amount_in_cents: number
  due_day: number
}

interface OneOffExpenseBody {
  id: number
  description: string
  amount_in_cents: number
  date: string
}

const toFixed = (body: FixedExpenseBody): FixedExpense => ({
  id: body.id,
  name: body.name,
  amountInCents: body.amount_in_cents,
  dueDay: body.due_day,
})

const toOneOff = (body: OneOffExpenseBody): OneOffExpense => ({
  id: body.id,
  description: body.description,
  amountInCents: body.amount_in_cents,
  date: body.date,
})

const fixedBody = (input: FixedExpenseInput) => ({ name: input.name, amount_in_cents: input.amountInCents, due_day: input.dueDay })

const oneOffBody = (input: OneOffExpenseInput) => ({
  description: input.description,
  amount_in_cents: input.amountInCents,
  date: input.date,
})

/** Every fixed expense and the one-off expenses of the current month. */
export async function fetchExpenses(): Promise<ExpenseList> {
  const { data } = await http<{ fixed: FixedExpenseBody[]; one_off: OneOffExpenseBody[] }>('/finance/expenses')
  return { fixed: data.fixed.map(toFixed), oneOff: data.one_off.map(toOneOff) }
}

export async function addFixedExpense(input: FixedExpenseInput): Promise<FixedExpense> {
  const { data } = await http<FixedExpenseBody>('/finance/fixed-expenses', { method: 'POST', body: fixedBody(input) })
  return toFixed(data)
}

export async function addOneOffExpense(input: OneOffExpenseInput): Promise<OneOffExpense> {
  const { data } = await http<OneOffExpenseBody>('/finance/one-off-expenses', { method: 'POST', body: oneOffBody(input) })
  return toOneOff(data)
}

export async function updateFixedExpense(id: number, input: FixedExpenseInput): Promise<FixedExpense> {
  const { data } = await http<FixedExpenseBody>(`/finance/fixed-expenses/${id}`, { method: 'PUT', body: fixedBody(input) })
  return toFixed(data)
}

export async function updateOneOffExpense(id: number, input: OneOffExpenseInput): Promise<OneOffExpense> {
  const { data } = await http<OneOffExpenseBody>(`/finance/one-off-expenses/${id}`, { method: 'PUT', body: oneOffBody(input) })
  return toOneOff(data)
}

export async function removeFixedExpense(id: number): Promise<void> {
  await http<void>(`/finance/fixed-expenses/${id}`, { method: 'DELETE' })
}

export async function removeOneOffExpense(id: number): Promise<void> {
  await http<void>(`/finance/one-off-expenses/${id}`, { method: 'DELETE' })
}

/** Undo of a removal. */
export async function restoreFixedExpense(id: number): Promise<FixedExpense> {
  const { data } = await http<FixedExpenseBody>(`/finance/fixed-expenses/${id}/restore`, { method: 'POST' })
  return toFixed(data)
}

export async function restoreOneOffExpense(id: number): Promise<OneOffExpense> {
  const { data } = await http<OneOffExpenseBody>(`/finance/one-off-expenses/${id}/restore`, { method: 'POST' })
  return toOneOff(data)
}
