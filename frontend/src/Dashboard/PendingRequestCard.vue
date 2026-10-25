<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import ClientNote from './ClientNote.vue'
import type { BookingRequest } from '@/Domain/Schedule/booking.model'
import { formatDistance } from '@/utils/address'
import { formatBRL } from '@/utils/currency'
import { formatDayMonth, formatWeekdayShort, fromIsoDate } from '@/utils/date'

/** `priceInCents`: the service's current price (unknown when the service was removed). */
const props = defineProps<{ request: BookingRequest; priceInCents: number | undefined }>()
const emit = defineEmits<{ accept: [requestId: number]; reject: [requestId: number] }>()
const { t } = useI18n()

const initials = computed(() =>
  props.request.clientName
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase(),
)
const schedule = computed(() => {
  const date = fromIsoDate(props.request.date)
  return t('dashboard.requestMeta', {
    date: `${formatWeekdayShort(date)}, ${formatDayMonth(date)}`,
    time: props.request.time,
  })
})
const distance = computed(() =>
  props.request.clientDistanceMeters === null
    ? t('dashboard.distanceUnknown')
    : t('dashboard.distance', { distance: formatDistance(props.request.clientDistanceMeters) }),
)
const price = computed(() => (props.priceInCents === undefined ? '' : formatBRL(props.priceInCents)))
</script>

<template>
  <article class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
    <div class="flex items-center gap-3">
      <span
        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-raised font-display font-extrabold ring-1 ring-line"
        aria-hidden="true"
      >
        {{ initials }}
      </span>
      <div class="min-w-0 flex-1">
        <h3 class="text-base break-words">{{ request.clientName }}</h3>
        <p class="text-sm text-muted">{{ schedule }}</p>
        <p class="text-xs text-muted">{{ distance }}</p>
      </div>
      <div class="shrink-0 text-right">
        <p class="text-sm font-semibold">{{ request.serviceName }}</p>
        <p class="font-display text-sm font-extrabold text-accent">{{ price }}</p>
      </div>
    </div>
    <ClientNote :note="request.clientNotes" />
    <div class="mt-4 grid grid-cols-2 gap-3">
      <BaseButton variant="danger" block @click="emit('reject', request.id)">{{ t('dashboard.reject') }}</BaseButton>
      <BaseButton variant="primary" elevated block @click="emit('accept', request.id)">{{ t('dashboard.accept') }}</BaseButton>
    </div>
  </article>
</template>
