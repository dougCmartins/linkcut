import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  createUnit as createUnitRequest,
  fetchOrganization,
  type NewUnitInput,
  type OrganizationSummary,
  type OrganizationUnit,
} from './organization.api'
import { setHttpUnitIdProvider } from '@/http/client'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { useTenantStore } from '@/Domain/Tenant/tenant.store'
import { useAgendaStore } from '@/Domain/Schedule/agenda.store'
import { useMonthToDateStore } from '@/Domain/Finance/monthToDate.store'
import { useTeamStore } from '@/Domain/Team/team.store'

const UNIT_KEY = 'linkcut.active-unit'

function readStoredUnitId(): number | null {
  try {
    const raw = localStorage.getItem(UNIT_KEY)
    if (!raw) return null
    const id = Number(raw)
    return Number.isFinite(id) ? id : null
  } catch {
    return null
  }
}

function writeStoredUnitId(id: number | null): void {
  try {
    if (id) localStorage.setItem(UNIT_KEY, String(id))
    else localStorage.removeItem(UNIT_KEY)
  } catch {
    // Storage blocked: the unit lasts while the page is open.
  }
}

export const useOrganizationStore = defineStore('organization', () => {
  const organization = ref<OrganizationSummary | null>(null)
  const activeUnitId = ref<number | null>(readStoredUnitId())
  let loading: Promise<void> | null = null

  const isMultiUnit = computed(() => organization.value?.type === 'multi_unit')
  const units = computed(() => organization.value?.units ?? [])
  const activeUnit = computed<OrganizationUnit | null>(
    () => units.value.find((unit) => unit.id === activeUnitId.value) ?? units.value.find((unit) => unit.isMain) ?? null,
  )

  setHttpUnitIdProvider(() => activeUnitId.value)

  async function ensureLoaded(): Promise<void> {
    if (organization.value) return
    loading ??= fetchOrganization()
      .then((loaded) => {
        organization.value = loaded
        const stored = activeUnitId.value
        const stillValid = stored !== null && loaded.units.some((unit) => unit.id === stored)
        const next = stillValid ? stored : (loaded.units.find((unit) => unit.isMain)?.id ?? null)
        activeUnitId.value = next
        writeStoredUnitId(next)
      })
      .catch(() => {
        organization.value = null
      })
      .finally(() => {
        loading = null
      })
    await loading
  }

  /** Loads the organization again (e.g. after a new unit), keeping the active unit when it still exists. */
  async function reload(): Promise<void> {
    organization.value = null
    await ensureLoaded()
  }

  /** Creates a branch and reloads the units; the caller decides whether to switch to it. */
  async function createUnit(input: NewUnitInput): Promise<OrganizationUnit> {
    const unit = await createUnitRequest(input)
    await reload()
    return unit
  }

  async function selectUnit(unitId: number): Promise<void> {
    if (activeUnitId.value === unitId) return
    if (!units.value.some((unit) => unit.id === unitId)) return
    activeUnitId.value = unitId
    writeStoredUnitId(unitId)

    const settings = useSettingsStore()
    const tenant = useTenantStore()
    const team = useTeamStore()
    settings.clear()
    tenant.clear()
    team.clear()
    await Promise.all([
      settings.ensureLoaded(),
      tenant.ensureLoaded(),
      useAgendaStore().load(),
      useMonthToDateStore().load(),
      team.ensureLoaded().catch(() => undefined),
    ])
  }

  function clear(): void {
    organization.value = null
    activeUnitId.value = null
    writeStoredUnitId(null)
  }

  return { organization, activeUnitId, activeUnit, units, isMultiUnit, ensureLoaded, reload, createUnit, selectUnit, clear }
})
