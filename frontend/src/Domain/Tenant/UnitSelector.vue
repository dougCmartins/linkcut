<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import SelectField from '@/components/ui/SelectField.vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useOrganizationStore } from './organization.store'

const ALL_UNITS = 'all'
const BRAND_ROUTE = 'brand-report'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const organizationStore = useOrganizationStore()

/** "Todas as unidades" is only for the brand owner, and only on the report screens. */
const offersAllUnits = computed(
  () => auth.user?.role === 'owner' && organizationStore.isMultiUnit && route.meta.reportScreen === true,
)
const onBrandReport = computed(() => route.name === BRAND_ROUTE)

const options = computed(() => [
  ...(offersAllUnits.value ? [{ value: ALL_UNITS, label: t('organization.allUnits') }] : []),
  ...organizationStore.units.map((unit) => ({
    value: String(unit.id),
    label: unit.isMain ? t('organization.mainUnit', { name: unit.name }) : unit.name,
  })),
])

const selected = computed({
  get: () => (onBrandReport.value ? ALL_UNITS : String(organizationStore.activeUnitId ?? '')),
  set: (value: string) => {
    if (value === ALL_UNITS) {
      void router.push({ name: BRAND_ROUTE })
      return
    }
    const id = Number(value)
    if (!Number.isFinite(id)) return
    void organizationStore.selectUnit(id)
    if (onBrandReport.value) void router.push({ name: 'history' })
  },
})
</script>

<template>
  <div v-if="organizationStore.isMultiUnit && organizationStore.units.length > 1" class="mx-auto w-full max-w-md px-4 pt-4">
    <SelectField v-model="selected" :label="t('organization.unitLabel')" :options="options" />
  </div>
</template>
