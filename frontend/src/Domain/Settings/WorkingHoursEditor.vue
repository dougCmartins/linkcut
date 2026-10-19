<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import type { DayHours } from './settings.model'
import { formatWeekdayLong } from '@/utils/date'

/** Monday first, Sunday last. */
const WEEK_ORDER = [1, 2, 3, 4, 5, 6, 0]

defineProps<{ errorFor: (field: string) => string | undefined }>()
const days = defineModel<DayHours[]>({ required: true })
const { t } = useI18n()

const rows = computed(() =>
  WEEK_ORDER.map((weekday) => {
    const index = days.value.findIndex((day) => day.weekday === weekday)
    const name = formatWeekdayLong(weekday)
    return { index, day: days.value[index], name: name.charAt(0).toUpperCase() + name.slice(1) }
  }).filter((row): row is { index: number; day: DayHours; name: string } => row.day !== undefined),
)

function updateDay(index: number, patch: Partial<DayHours>): void {
  days.value = days.value.map((day, position) => (position === index ? { ...day, ...patch } : day))
}
</script>

<template>
  <ul class="grid gap-3">
    <li v-for="row in rows" :key="row.day.weekday" class="rounded-card bg-raised p-4 ring-1 ring-line">
      <ToggleSwitch
        :model-value="row.day.isOpen"
        :label="row.name"
        :description="row.day.isOpen ? `${row.day.opensAt} – ${row.day.closesAt}` : t('settings.schedule.closed')"
        @update:model-value="updateDay(row.index, { isOpen: $event })"
      />
      <div v-if="row.day.isOpen" class="mt-3 grid grid-cols-2 gap-3">
        <TextField
          type="time"
          step="900"
          :model-value="row.day.opensAt"
          :label="t('settings.schedule.opensAt')"
          @update:model-value="updateDay(row.index, { opensAt: $event })"
        />
        <TextField
          type="time"
          step="900"
          :model-value="row.day.closesAt"
          :label="t('settings.schedule.closesAt')"
          @update:model-value="updateDay(row.index, { closesAt: $event })"
        />
      </div>
      <p v-if="errorFor(`weeklyHours.${row.index}`)" class="mt-2 pl-2 text-xs text-danger" role="alert">
        {{ errorFor(`weeklyHours.${row.index}`) }}
      </p>
    </li>
  </ul>
</template>
