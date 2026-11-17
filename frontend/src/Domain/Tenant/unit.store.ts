import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { ApiError } from '@/http/apiError'
import { setActiveUnit } from '@/http/client'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { fetchUnits, type UnitList } from './unit.api'

/** The unit picked at the top of the dashboard, kept on this device (a per-device convenience). */
const ACTIVE_UNIT_KEY = 'linkcut.active-unit'

function savedUnit(): number | null {
  try {
    const saved = Number(localStorage.getItem(ACTIVE_UNIT_KEY))
    return Number.isInteger(saved) && saved > 0 ? saved : null
  } catch {
    return null
  }
}

function saveUnit(unitId: number | null): void {
  try {
    if (unitId === null) localStorage.removeItem(ACTIVE_UNIT_KEY)
    else localStorage.setItem(ACTIVE_UNIT_KEY, String(unitId))
  } catch {
    // Storage blocked: the dashboard opens on the barber's own unit.
  }
}

/** Called once before the app mounts, so the first calls of the dashboard already go to the picked unit. */
export function restoreActiveUnit(): void {
  setActiveUnit(savedUnit())
}

/** Starts the whole dashboard again on the new unit: every store reloads its data (agenda, finance, settings). */
function reloadDashboard(): void {
  window.location.reload()
}

/**
 * Main unit and branches (docs/adr/0002). The picker shows only for a brand with branches; a barber on their own
 * never sees it. Switching saves the unit and reloads the dashboard on it.
 */
export const useUnitStore = defineStore('unit', () => {
  const list = ref<UnitList | null>(null)
  const activeUnitId = ref<number | null>(savedUnit())

  const isMultiUnit = computed(() => list.value?.organizationType === 'multi_unit' && list.value.units.length > 1)
  const activeUnit = computed(() => {
    const units = list.value?.units ?? []
    return units.find((unit) => unit.id === activeUnitId.value) ?? units.find((unit) => unit.isMain) ?? units[0] ?? null
  })

  async function load(): Promise<void> {
    try {
      list.value = await fetchUnits()
    } catch (caught) {
      // A unit saved on this device that is gone or no longer open to this account: back to the own unit.
      if (caught instanceof ApiError && caught.code === ApiErrorCode.UnitForbidden) {
        forget()
        reloadDashboard()
      }
      return
    }
    if (activeUnitId.value !== null && !list.value.units.some((unit) => unit.id === activeUnitId.value)) {
      forget()
      reloadDashboard()
    }
  }

  function switchTo(unitId: number): void {
    if (unitId === activeUnit.value?.id) return
    const main = list.value?.units.find((unit) => unit.isMain)
    const picked = unitId === main?.id ? null : unitId
    saveUnit(picked)
    setActiveUnit(picked)
    activeUnitId.value = picked
    reloadDashboard()
  }

  /** Sign-out or a gone unit: the next session starts on the barber's own unit. */
  function forget(): void {
    saveUnit(null)
    setActiveUnit(null)
    activeUnitId.value = null
    list.value = null
  }

  return { list, activeUnit, isMultiUnit, load, switchTo, forget }
})
