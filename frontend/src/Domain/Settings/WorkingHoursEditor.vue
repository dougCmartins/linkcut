<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import type { DayHours } from './settings.model'
import { formatWeekdayLong } from '@/utils/date'

/** Monday first, Sunday last. */
const WEEK_ORDER = [1, 2, 3, 4, 5, 6, 0]
const DEFAULT_BREAK = { breakStartsAt: '12:00', breakEndsAt: '13:00' }

/** A day with the optional break (a barber's hours); the unit's hours have none. */
type EditableDay = DayHours & { breakStartsAt?: string | null; breakEndsAt?: string | null }

withDefaults(defineProps<{ errorFor: (field: string) => string | undefined; withBreak?: boolean }>(), { withBreak: false })
const days = defineModel<EditableDay[]>({ required: true })
const { t } = useI18n()

const rows = computed(() =>
  WEEK_ORDER.map((weekday) => {
    const index = days.value.findIndex((day) => day.weekday === weekday)
    const name = formatWeekdayLong(weekday)
    return { index, day: days.value[index], name: name.charAt(0).toUpperCase() + name.slice(1) }
  }).filter((row): row is { index: number; day: EditableDay; name: string } => row.day !== undefined),
)

function updateDay(index: number, patch: Partial<EditableDay>): void {
  days.value = days.value.map((day, position) => (position === index ? { ...day, ...patch } : day))
}

const hasBreak = (day: EditableDay) => Boolean(day.breakStartsAt && day.breakEndsAt)

function toggleBreak(index: number, isOn: boolean): void {
  updateDay(index, isOn ? DEFAULT_BREAK : { breakStartsAt: null, breakEndsAt: null })
}

/** "Repetir nos outros dias": the same break on every other open day. */
function repeatBreak(source: EditableDay): void {
  days.value = days.value.map((day) =>
    day.isOpen && day.weekday !== source.weekday
      ? { ...day, breakStartsAt: source.breakStartsAt ?? null, breakEndsAt: source.breakEndsAt ?? null }
      : day,
  )
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
      <template v-if="withBreak && row.day.isOpen">
        <ToggleSwitch
          class="mt-3"
          :model-value="hasBreak(row.day)"
          :label="t('team.agenda.hours.break')"
          :description="hasBreak(row.day) ? `${row.day.breakStartsAt} – ${row.day.breakEndsAt}` : t('team.agenda.hours.noBreak')"
          @update:model-value="toggleBreak(row.index, $event)"
        />
        <div v-if="hasBreak(row.day)" class="mt-3 grid grid-cols-2 gap-3">
          <TextField
            type="time"
            step="900"
            :model-value="row.day.breakStartsAt ?? ''"
            :label="t('team.agenda.hours.breakStartsAt')"
            @update:model-value="updateDay(row.index, { breakStartsAt: $event })"
          />
          <TextField
            type="time"
            step="900"
            :model-value="row.day.breakEndsAt ?? ''"
            :label="t('team.agenda.hours.breakEndsAt')"
            @update:model-value="updateDay(row.index, { breakEndsAt: $event })"
          />
          <button type="button" class="col-span-2 justify-self-start text-sm font-semibold text-accent" @click="repeatBreak(row.day)">
            {{ t('team.agenda.hours.repeatBreak') }}
          </button>
        </div>
      </template>
      <p v-if="errorFor(`weeklyHours.${row.index}`)" class="mt-2 pl-2 text-xs text-danger" role="alert">
        {{ errorFor(`weeklyHours.${row.index}`) }}
      </p>
    </li>
  </ul>
</template>
