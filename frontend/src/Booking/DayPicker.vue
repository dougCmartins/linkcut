<script setup lang="ts">
import { computed } from 'vue'
import { formatDayMonth, formatWeekdayShort, toIsoDate } from '@/utils/date'

const props = defineProps<{ days: Date[]; selectedDate: string | null; label: string }>()
const emit = defineEmits<{ select: [isoDate: string] }>()

const options = computed(() =>
  props.days.map((day) => ({
    isoDate: toIsoDate(day),
    weekday: formatWeekdayShort(day),
    dayNumber: day.getDate(),
    fullLabel: `${formatWeekdayShort(day)} ${formatDayMonth(day)}`,
  })),
)
</script>

<template>
  <div role="radiogroup" :aria-label="label" class="scrollbar-none -mx-5 flex snap-x gap-2.5 overflow-x-auto px-5 py-2">
    <button
      v-for="option in options"
      :key="option.isoDate"
      type="button"
      role="radio"
      :aria-checked="option.isoDate === selectedDate"
      :aria-label="option.fullLabel"
      class="flex w-16 shrink-0 snap-start flex-col items-center gap-1 rounded-2xl py-3 ring-1 transition duration-150"
      :class="
        option.isoDate === selectedDate
          ? 'bg-accent text-on-accent ring-accent shadow-float -translate-y-0.5'
          : 'bg-surface text-ink ring-line shadow-soft hover:-translate-y-0.5'
      "
      @click="emit('select', option.isoDate)"
    >
      <span class="text-[11px] font-semibold tracking-wider uppercase opacity-75">{{ option.weekday }}</span>
      <span class="font-display text-xl font-extrabold tracking-tight">{{ option.dayNumber }}</span>
    </button>
  </div>
</template>
