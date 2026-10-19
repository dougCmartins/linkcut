import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { computeFinancialSummary, type MonthFinances } from './financialSummary'
import { fetchMonthToDateFinances } from './dashboard.api'

/** Dashboard numbers: always the current month, from day 1 until now (not customizable). */
export const useDashboardStore = defineStore('dashboard', () => {
  const finances = ref<MonthFinances | null>(null)
  const isLoading = ref(false)

  const summary = computed(() =>
    finances.value ? computeFinancialSummary(finances.value.revenueInCents, finances.value.expenses.totalInCents) : null,
  )

  async function loadMonthToDate(now = new Date()): Promise<void> {
    isLoading.value = true
    try {
      finances.value = await fetchMonthToDateFinances(now)
    } finally {
      isLoading.value = false
    }
  }

  return { finances, isLoading, summary, loadMonthToDate }
})
