<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import SelectField from '@/components/ui/SelectField.vue'
import { useUnitStore } from './unit.store'

/**
 * "Unidade" at the top of the dashboard pages: the agenda, history, finance and settings follow the unit picked here.
 * Renders nothing for a barber on their own (individual brand, one unit).
 */
const { t } = useI18n()
const unitStore = useUnitStore()

onMounted(() => {
  if (!unitStore.list) void unitStore.load()
})

const options = computed(() =>
  (unitStore.list?.units ?? []).map((unit) => ({
    value: String(unit.id),
    label: unit.isMain ? t('units.mainUnit', { name: unit.name }) : unit.name,
  })),
)

const picked = computed({
  get: () => String(unitStore.activeUnit?.id ?? ''),
  set: (value: string) => unitStore.switchTo(Number(value)),
})
</script>

<template>
  <div v-if="unitStore.isMultiUnit" class="mb-4">
    <SelectField v-model="picked" :label="t('units.label')" :options="options" />
  </div>
</template>
