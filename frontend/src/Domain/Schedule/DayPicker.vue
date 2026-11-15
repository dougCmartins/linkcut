<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import HorizontalScroller from '@/components/ui/HorizontalScroller.vue'
import { formatDayMonth, formatWeekdayShort, toIsoDate } from '@/utils/date'

const props = withDefaults(
  defineProps<{ days: Date[]; selectedDate: string | null; label: string; unavailableDates?: string[] }>(),
  { unavailableDates: () => [] },
)
const emit = defineEmits<{ select: [isoDate: string] }>()
const { t } = useI18n()

const options = computed(() =>
  props.days.map((day) => {
    const isoDate = toIsoDate(day)
    const isUnavailable = props.unavailableDates.includes(isoDate)
    const dayLabel = `${formatWeekdayShort(day)} ${formatDayMonth(day)}`
    return {
      isoDate,
      isUnavailable,
      weekday: formatWeekdayShort(day),
      dayNumber: day.getDate(),
      fullLabel: isUnavailable ? `${dayLabel}, ${t('booking.dayUnavailable')}` : dayLabel,
    }
  }),
)

function optionClass(option: { isoDate: string; isUnavailable: boolean }): string {
  if (option.isoDate === props.selectedDate) return 'bg-accent text-on-accent ring-accent shadow-float -translate-y-0.5'
  if (option.isUnavailable) return 'bg-raised/60 text-muted/60 ring-line line-through cursor-not-allowed'
  return 'bg-surface text-ink ring-line shadow-soft hover:-translate-y-0.5'
}
</script>

<template>
  <!-- The bottom padding (taken back by the negative margin) keeps the cards' shadow inside the scrolling row. -->
  <HorizontalScroller :label="label" :selected-key="selectedDate" class="-mx-2 -mb-6 gap-2.5 px-2.5 pt-2 pb-8">
    <button
      v-for="option in options"
      :key="option.isoDate"
      type="button"
      role="radio"
      :aria-checked="option.isoDate === selectedDate"
      :aria-label="option.fullLabel"
      :disabled="option.isUnavailable"
      class="flex w-16 shrink-0 snap-start flex-col items-center gap-1 rounded-2xl py-3 ring-1 transition duration-150"
      :class="optionClass(option)"
      @click="emit('select', option.isoDate)"
    >
      <span class="text-[11px] font-semibold tracking-wider uppercase opacity-75">{{ option.weekday }}</span>
      <span class="font-display text-xl font-extrabold tracking-tight">{{ option.dayNumber }}</span>
    </button>
  </HorizontalScroller>
</template>
