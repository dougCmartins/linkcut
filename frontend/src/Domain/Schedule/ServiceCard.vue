<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Service } from './booking.model'
import { formatBRL } from '@/utils/currency'

const props = defineProps<{ service: Service; selected: boolean }>()
const emit = defineEmits<{ select: [serviceId: string] }>()
const { t } = useI18n()

const price = computed(() => formatBRL(props.service.priceInCents))
</script>

<template>
  <button
    type="button"
    role="radio"
    :aria-checked="selected"
    class="flex w-full items-center gap-4 rounded-card bg-surface p-4 text-left ring-1 transition duration-150 hover:-translate-y-0.5"
    :class="selected ? 'shadow-float ring-2 ring-accent' : 'shadow-soft ring-line'"
    @click="emit('select', service.id)"
  >
    <span
      class="flex size-12 shrink-0 items-center justify-center rounded-2xl"
      :class="selected ? 'bg-accent text-on-accent' : 'bg-raised text-ink'"
      aria-hidden="true"
    >
      <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="6" cy="6" r="3" />
        <circle cx="6" cy="18" r="3" />
        <path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12" />
      </svg>
    </span>
    <span class="min-w-0 flex-1">
      <span class="block font-display text-lg font-extrabold tracking-tight">{{ service.name }}</span>
      <span class="block text-sm text-muted">{{ t('booking.duration', { minutes: service.durationMinutes }) }}</span>
    </span>
    <span class="font-display text-lg font-extrabold tracking-tight">{{ price }}</span>
  </button>
</template>
