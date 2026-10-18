import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { computeFinancialSummary } from './financialSummary'
import { monthlyFinances } from '@/mocks/barber'

export const useDashboardStore = defineStore('dashboard', () => {
  const revenueInCents = ref(monthlyFinances.revenueInCents)
  const expensesInCents = ref(monthlyFinances.expensesInCents)

  const summary = computed(() => computeFinancialSummary(revenueInCents.value, expensesInCents.value))

  return { revenueInCents, expensesInCents, summary }
})
