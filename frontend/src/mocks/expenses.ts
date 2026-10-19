import type { ExpenseList } from '@/Expenses/expenses.model'
import { toIsoDate } from '@/utils/date'

/**
 * Mock expenses kept in the browser until the backend exists.
 * On the first visit it starts with editable examples, so the dashboard margin is not 100%.
 */
const STORAGE_KEY = 'linkcut.mockExpenses'

function examples(now: Date): ExpenseList {
  const dayOfMonth = (day: number) => toIsoDate(new Date(now.getFullYear(), now.getMonth(), Math.min(day, now.getDate())))
  return {
    fixed: [
      { id: 'fixed-rent', name: 'Aluguel da cadeira', amountInCents: 150_000, dueDay: 5 },
      { id: 'fixed-internet', name: 'Internet', amountInCents: 12_000, dueDay: 10 },
      { id: 'fixed-mei', name: 'DAS (MEI)', amountInCents: 7_590, dueDay: 20 },
    ],
    oneOff: [
      { id: 'oneoff-blades', description: 'Lâminas e pomadas', amountInCents: 21_000, date: dayOfMonth(2) },
      { id: 'oneoff-towels', description: 'Toalhas descartáveis', amountInCents: 8_500, date: dayOfMonth(12) },
    ],
  }
}

/** Copy used when the browser blocks storage, so changes last while the page is open. */
let memory: ExpenseList | null = null

export function mockReadExpenses(now: Date): ExpenseList {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) return JSON.parse(raw) as ExpenseList
  } catch {
    if (memory) return memory
  }
  const seeded = examples(now)
  mockWriteExpenses(seeded)
  return seeded
}

export function mockWriteExpenses(expenses: ExpenseList): void {
  memory = expenses
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(expenses))
  } catch {
    // Storage blocked: the in-memory copy is used.
  }
}
