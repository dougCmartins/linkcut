import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ExpenseBreakdown, ExpenseList, FixedExpense, FixedExpenseInput, OneOffExpense, OneOffExpenseInput } from './expenses.model'
import {
  addFixedExpense,
  addOneOffExpense,
  fetchExpenses,
  removeFixedExpense,
  removeOneOffExpense,
  restoreFixedExpense,
  restoreOneOffExpense,
  updateFixedExpense,
  updateOneOffExpense,
} from './expenses.api'
import { fetchMonthToDateFinances } from '../monthToDate.api'

export type Expense = FixedExpense | OneOffExpense

export const isFixed = (expense: Expense): expense is FixedExpense => 'dueDay' in expense

const byDueDay = (a: FixedExpense, b: FixedExpense) => a.dueDay - b.dueDay || a.id - b.id
const newestFirst = (a: OneOffExpense, b: OneOffExpense) => b.date.localeCompare(a.date) || b.id - a.id

/** The "Despesas" screen. Every change is saved on the API, then the month totals are read again. */
export const useExpensesStore = defineStore('expenses', () => {
  const expenses = ref<ExpenseList>({ fixed: [], oneOff: [] })
  /** Month-to-date totals (includes the cost of concluded appointments). */
  const breakdown = ref<ExpenseBreakdown | null>(null)
  const isLoading = ref(false)
  const hasFailed = ref(false)

  const fixedByDueDay = computed(() => [...expenses.value.fixed].sort(byDueDay))
  /** The API sends only the one-off expenses of the current month. */
  const oneOffOfMonth = computed(() => [...expenses.value.oneOff].sort(newestFirst))

  async function refreshBreakdown(): Promise<void> {
    breakdown.value = (await fetchMonthToDateFinances()).expenses
  }

  async function load(): Promise<void> {
    isLoading.value = true
    hasFailed.value = false
    try {
      const [list] = await Promise.all([fetchExpenses(), refreshBreakdown()])
      expenses.value = list
    } catch {
      hasFailed.value = true
    } finally {
      isLoading.value = false
    }
  }

  /** Puts the saved version of an expense in the list (replacing the old one, if any). */
  function put(expense: Expense): void {
    const others = <T extends Expense>(list: T[]) => list.filter((item) => item.id !== expense.id)
    expenses.value = isFixed(expense)
      ? { ...expenses.value, fixed: [...others(expenses.value.fixed), expense] }
      : { ...expenses.value, oneOff: [...others(expenses.value.oneOff), expense] }
  }

  async function addFixed(input: FixedExpenseInput): Promise<void> {
    put(await addFixedExpense(input))
    await refreshBreakdown()
  }

  async function addOneOff(input: OneOffExpenseInput): Promise<void> {
    put(await addOneOffExpense(input))
    await refreshBreakdown()
  }

  async function updateFixed(id: number, input: FixedExpenseInput): Promise<void> {
    put(await updateFixedExpense(id, input))
    await refreshBreakdown()
  }

  async function updateOneOff(id: number, input: OneOffExpenseInput): Promise<void> {
    put(await updateOneOffExpense(id, input))
    await refreshBreakdown()
  }

  /** Removes and returns the expense, so it can be restored (undo). */
  async function remove(expense: Expense): Promise<Expense> {
    if (isFixed(expense)) await removeFixedExpense(expense.id)
    else await removeOneOffExpense(expense.id)
    expenses.value = {
      fixed: expenses.value.fixed.filter((item) => !isFixed(expense) || item.id !== expense.id),
      oneOff: expenses.value.oneOff.filter((item) => isFixed(expense) || item.id !== expense.id),
    }
    await refreshBreakdown()
    return expense
  }

  async function restore(expense: Expense): Promise<void> {
    put(isFixed(expense) ? await restoreFixedExpense(expense.id) : await restoreOneOffExpense(expense.id))
    await refreshBreakdown()
  }

  return {
    expenses,
    breakdown,
    isLoading,
    hasFailed,
    fixedByDueDay,
    oneOffOfMonth,
    load,
    addFixed,
    addOneOff,
    updateFixed,
    updateOneOff,
    remove,
    restore,
  }
})
