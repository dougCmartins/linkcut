<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ChoiceChips from '@/components/ui/ChoiceChips.vue'
import TextField from '@/components/ui/TextField.vue'
import { HistoryPeriod, type DateRange } from './history.model'

defineProps<{ maxDate: string; error?: string }>()
const period = defineModel<HistoryPeriod>('period', { required: true })
const customRange = defineModel<DateRange>('customRange', { required: true })
const { t } = useI18n()

const periodOptions = computed(() =>
  Object.values(HistoryPeriod).map((value) => ({ value, label: t(`history.periods.${value}`) })),
)

function updateRange(patch: Partial<DateRange>): void {
  customRange.value = { ...customRange.value, ...patch }
}
</script>

<template>
  <div class="grid gap-4">
    <ChoiceChips v-model="period" :options="periodOptions" :label="t('history.periodLabel')" />
    <div v-if="period === HistoryPeriod.Custom" class="grid grid-cols-2 gap-3">
      <TextField
        type="date"
        :model-value="customRange.from"
        :max="maxDate"
        :label="t('history.from')"
        @update:model-value="updateRange({ from: $event })"
      />
      <TextField
        type="date"
        :model-value="customRange.to"
        :max="maxDate"
        :label="t('history.to')"
        :error="error"
        @update:model-value="updateRange({ to: $event })"
      />
    </div>
  </div>
</template>
