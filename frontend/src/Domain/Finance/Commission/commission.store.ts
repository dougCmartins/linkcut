import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from './commission.api'
import type {
  CommissionAdjustment,
  CommissionAdjustmentInput,
  CommissionClosing,
  CommissionReport,
  CommissionRule,
  CommissionSimulation,
  NewCommissionRule,
  ReportRange,
  SimulationInput,
} from './commission.model'

/** Owner-side configuration (rules, adjustments, closings) and the report (owner or barber). */
export const useCommissionStore = defineStore('commission', () => {
  const rules = ref<CommissionRule[]>([])
  const adjustments = ref<CommissionAdjustment[]>([])
  const closings = ref<CommissionClosing[]>([])
  const report = ref<CommissionReport | null>(null)
  const configLoaded = ref(false)

  async function loadConfig(): Promise<void> {
    const [loadedRules, loadedAdjustments, loadedClosings] = await Promise.all([
      api.fetchRules(),
      api.fetchAdjustments(),
      api.fetchClosings(),
    ])
    rules.value = loadedRules
    adjustments.value = loadedAdjustments
    closings.value = loadedClosings
    configLoaded.value = true
  }

  async function addRule(rule: NewCommissionRule): Promise<void> {
    rules.value = [...rules.value, await api.createRule(rule)]
  }

  async function removeRule(id: number): Promise<void> {
    await api.deleteRule(id)
    rules.value = rules.value.filter((rule) => rule.id !== id)
  }

  async function addAdjustment(input: CommissionAdjustmentInput): Promise<void> {
    adjustments.value = [...adjustments.value, await api.createAdjustment(input)]
  }

  async function saveAdjustment(id: number, input: CommissionAdjustmentInput): Promise<void> {
    const updated = await api.updateAdjustment(id, input)
    adjustments.value = adjustments.value.map((adjustment) => (adjustment.id === id ? updated : adjustment))
  }

  async function removeAdjustment(id: number): Promise<void> {
    await api.deleteAdjustment(id)
    adjustments.value = adjustments.value.filter((adjustment) => adjustment.id !== id)
  }

  function simulate(input: SimulationInput): Promise<CommissionSimulation> {
    return api.simulate(input)
  }

  async function loadReport(range: ReportRange, staffId: number | null): Promise<void> {
    report.value = await api.fetchReport(range, staffId)
  }

  async function closePeriod(range: ReportRange): Promise<void> {
    const closing = await api.closePeriod(range)
    closings.value = [closing, ...closings.value]
  }

  async function reopen(id: number): Promise<void> {
    const updated = await api.reopenClosing(id)
    closings.value = closings.value.map((closing) => (closing.id === id ? updated : closing))
  }

  function clear(): void {
    rules.value = []
    adjustments.value = []
    closings.value = []
    report.value = null
    configLoaded.value = false
  }

  return {
    rules,
    adjustments,
    closings,
    report,
    configLoaded,
    loadConfig,
    addRule,
    removeRule,
    addAdjustment,
    saveAdjustment,
    removeAdjustment,
    simulate,
    loadReport,
    closePeriod,
    reopen,
    clear,
  }
})
