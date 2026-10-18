<script setup lang="ts">
import HorizontalScroller from '@/components/ui/HorizontalScroller.vue'

defineProps<{ times: string[]; selectedTime: string | null; label: string; emptyText: string }>()
const emit = defineEmits<{ select: [time: string] }>()
</script>

<template>
  <p v-if="times.length === 0" class="rounded-card bg-raised px-4 py-3 text-sm text-muted">{{ emptyText }}</p>
  <HorizontalScroller v-else :label="label" :selected-key="selectedTime" class="gap-2 px-0.5 py-2">
    <button
      v-for="time in times"
      :key="time"
      type="button"
      role="radio"
      :aria-checked="time === selectedTime"
      class="h-11 shrink-0 snap-start rounded-full px-5 font-display text-sm font-extrabold tracking-tight ring-1 transition duration-150"
      :class="
        time === selectedTime
          ? 'bg-accent text-on-accent ring-accent shadow-soft'
          : 'bg-surface text-ink ring-line shadow-soft hover:-translate-y-0.5'
      "
      @click="emit('select', time)"
    >
      {{ time }}
    </button>
  </HorizontalScroller>
</template>
