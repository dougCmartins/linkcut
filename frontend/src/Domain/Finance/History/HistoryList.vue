<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { DayGroup } from './historySummary'
import { formatBRL } from '@/utils/currency'
import { capitalize, formatLongDate, fromIsoDate } from '@/utils/date'

const props = defineProps<{ groups: DayGroup[]; currentYear: number }>()
const { t } = useI18n()

function dayLabel(isoDate: string): string {
  const date = fromIsoDate(isoDate)
  return capitalize(formatLongDate(date, { withYear: date.getFullYear() !== props.currentYear }))
}
</script>

<template>
  <ol class="grid gap-5">
    <li v-for="group in groups" :key="group.date">
      <div class="flex items-baseline justify-between gap-3 px-1 pb-2">
        <h3 class="text-sm">
          <time :datetime="group.date">{{ dayLabel(group.date) }}</time>
        </h3>
        <p class="shrink-0 text-xs font-bold text-muted">
          {{ t('history.dayTotal', { count: group.records.length, total: formatBRL(group.totalInCents) }) }}
        </p>
      </div>
      <ul class="divide-y divide-line overflow-hidden rounded-card bg-surface ring-1 ring-line shadow-soft">
        <li v-for="record in group.records" :key="record.id" class="flex items-center gap-3 px-4 py-3">
          <span class="w-12 shrink-0 font-display text-sm font-extrabold tabular-nums text-muted">{{ record.time }}</span>
          <span class="min-w-0 flex-1">
            <span class="block font-semibold break-words">{{ record.clientName }}</span>
            <span class="block text-sm text-muted break-words">{{ record.serviceName }}</span>
          </span>
          <span class="shrink-0 font-display font-extrabold tabular-nums">{{ formatBRL(record.priceInCents) }}</span>
        </li>
      </ul>
    </li>
  </ol>
</template>
