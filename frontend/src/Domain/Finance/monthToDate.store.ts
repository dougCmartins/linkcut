import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { computeFinancialSummary, type MonthFinances } from './financialSummary'
import { fetchMonthToDateFinances } from './monthToDate.api'

/** Dashboard numbers: always the current month, from day 1 until today (not customizable). */
export const useMonthToDateStore = defineStore('monthToDate', () => {
  const finances = ref<MonthFinances | null>(null)
  const isLoading = ref(false)
  const hasFailed = ref(false)

  const summary = computed(() =>
    finances.value ? computeFinancialSummary(finances.value.revenueInCents, finances.value.expenses.totalInCents) : null,
  )

  async function load(): Promise<void> {
    isLoading.value = true
    hasFailed.value = false
    try {
      finances.value = await fetchMonthToDateFinances()
    } catch {
      hasFailed.value = true
    } finally {
      isLoading.value = false
    }
  }

  return { finances, isLoading, hasFailed, summary, load }
})
