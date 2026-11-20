<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SelectField from '@/components/ui/SelectField.vue'
import { useOrganizationStore } from './organization.store'

const { t } = useI18n()
const organizationStore = useOrganizationStore()

const options = computed(() =>
  organizationStore.units.map((unit) => ({
    value: String(unit.id),
    label: unit.isMain ? t('organization.mainUnit', { name: unit.name }) : unit.name,
  })),
)

const selected = computed({
  get: () => String(organizationStore.activeUnitId ?? ''),
  set: (value: string) => {
    const id = Number(value)
    if (Number.isFinite(id)) void organizationStore.selectUnit(id)
  },
})
</script>

<template>
  <div v-if="organizationStore.isMultiUnit && options.length > 1" class="mx-auto w-full max-w-md px-4 pt-4">
    <SelectField v-model="selected" :label="t('organization.unitLabel')" :options="options" />
  </div>
</template>
