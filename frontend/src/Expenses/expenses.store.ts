import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ExpenseBreakdown, ExpenseList, FixedExpense, FixedExpenseInput, OneOffExpense, OneOffExpenseInput } from './expenses.model'
import { addFixedExpense, addOneOffExpense, fetchExpenses, removeExpense, restoreExpense, updateExpense } from './expenses.api'
import { fetchMonthToDateFinances } from '@/Dashboard/dashboard.api'
import { toIsoDate } from '@/utils/date'

export type Expense = FixedExpense | OneOffExpense

export const useExpensesStore = defineStore('expenses', () => {
  const expenses = ref<ExpenseList>({ fixed: [], oneOff: [] })
  /** Month-to-date totals (includes the cost of concluded appointments). */
  const breakdown = ref<ExpenseBreakdown | null>(null)
  const isLoading = ref(false)
  const hasFailed = ref(false)

  const fixedByDueDay = computed(() => [...expenses.value.fixed].sort((a, b) => a.dueDay - b.dueDay))

  /** One-off expenses of the month of `now`, newest first. */
  function oneOffOfMonth(now: Date): OneOffExpense[] {
    const month = toIsoDate(now).slice(0, 7)
    return expenses.value.oneOff
      .filter((expense) => expense.date.startsWith(month))
      .sort((a, b) => b.date.localeCompare(a.date))
  }

  async function refreshBreakdown(now: Date): Promise<void> {
    breakdown.value = (await fetchMonthToDateFinances(now)).expenses
  }

  async function load(now = new Date()): Promise<void> {
    isLoading.value = true
    hasFailed.value = false
    try {
      expenses.value = await fetchExpenses(now)
      await refreshBreakdown(now)
    } catch {
      hasFailed.value = true
    } finally {
      isLoading.value = false
    }
  }

  async function addFixed(input: FixedExpenseInput, now = new Date()): Promise<void> {
    expenses.value.fixed.push(await addFixedExpense(input, now))
    await refreshBreakdown(now)
  }

  async function addOneOff(input: OneOffExpenseInput, now = new Date()): Promise<void> {
    expenses.value.oneOff.push(await addOneOffExpense(input, now))
    await refreshBreakdown(now)
  }

  /** Saves the edited values of an existing expense (same id). */
  async function update(expense: Expense, now = new Date()): Promise<void> {
    await updateExpense(expense, now)
    expenses.value = {
      fixed: expenses.value.fixed.map((item) => (item.id === expense.id && 'dueDay' in expense ? expense : item)),
      oneOff: expenses.value.oneOff.map((item) => (item.id === expense.id && 'date' in expense ? expense : item)),
    }
    await refreshBreakdown(now)
  }

  /** Removes and returns the expense, so it can be restored (undo). */
  async function remove(id: string, now = new Date()): Promise<Expense | null> {
    const removed = [...expenses.value.fixed, ...expenses.value.oneOff].find((expense) => expense.id === id) ?? null
    if (!removed) return null
    await removeExpense(id, now)
    expenses.value = {
      fixed: expenses.value.fixed.filter((expense) => expense.id !== id),
      oneOff: expenses.value.oneOff.filter((expense) => expense.id !== id),
    }
    await refreshBreakdown(now)
    return removed
  }

  async function restore(expense: Expense, now = new Date()): Promise<void> {
    await restoreExpense(expense, now)
    if ('dueDay' in expense) expenses.value.fixed.push(expense)
    else expenses.value.oneOff.push(expense)
    await refreshBreakdown(now)
  }

  return { expenses, breakdown, isLoading, hasFailed, fixedByDueDay, oneOffOfMonth, load, addFixed, addOneOff, update, remove, restore }
})
