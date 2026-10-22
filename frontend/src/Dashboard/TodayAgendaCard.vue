<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { BookingRequest } from '@/Domain/Schedule/booking.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { formatDistance } from '@/utils/address'
import { formatBRL } from '@/utils/currency'
import { buildWhatsappUrl } from '@/utils/phone'

/** `priceInCents`: the service's current price (shown until it is concluded with its own). */
const props = withDefaults(
  defineProps<{
    request: BookingRequest
    priceInCents: number | undefined
    isBusy: boolean
    /** For the "Lembrar no WhatsApp" message. */
    barbershopName?: string
    barbershopAddress?: string
  }>(),
  { barbershopName: '', barbershopAddress: '' },
)
const emit = defineEmits<{ complete: [requestId: number] }>()
const { t } = useI18n()

const isCompleted = computed(() => props.request.status === AppointmentStatus.Completed)
/** Cancelled by the client in the client area: stays visible (greyed out) so the barber knows the time is free. */
const isCancelled = computed(() => props.request.status === AppointmentStatus.Cancelled)
const isDone = computed(() => isCompleted.value || isCancelled.value)
const distance = computed(() =>
  props.request.clientDistanceMeters === null
    ? t('dashboard.distanceUnknown')
    : t('dashboard.distance', { distance: formatDistance(props.request.clientDistanceMeters) }),
)
/** One tap: WhatsApp opens with the reminder ready (sent by the barber; no paid API). */
const remindUrl = computed(() => {
  const { clientName, time } = props.request
  const firstName = clientName.trim().split(' ')[0]
  const greeting = t('dashboard.agenda.remindMessage', { client: firstName, time, barbershop: props.barbershopName })
  const address = props.barbershopAddress ? ` ${t('dashboard.agenda.remindAddress', { address: props.barbershopAddress })}` : ''
  return buildWhatsappUrl(props.request.clientWhatsapp, `${greeting}${address}`)
})
const price = computed(() => formatBRL(props.request.completedPriceInCents ?? props.priceInCents ?? 0))
</script>

<template>
  <article
    class="rounded-card p-4 ring-1 transition"
    :class="isDone ? 'bg-raised/60 ring-line' : 'bg-surface ring-line shadow-soft'"
  >
    <div class="flex items-center gap-3">
      <span
        class="flex h-12 w-14 shrink-0 items-center justify-center rounded-2xl font-display text-sm font-extrabold tabular-nums"
        :class="isCompleted ? 'bg-success/15 text-success' : 'bg-raised text-ink'"
      >
        {{ request.time }}
      </span>
      <div class="min-w-0 flex-1">
        <h3 class="text-base break-words" :class="isDone ? 'text-muted' : ''">{{ request.clientName }}</h3>
        <p class="text-sm text-muted break-words">{{ request.serviceName }}</p>
        <p class="text-xs text-muted">{{ distance }}</p>
      </div>
      <p
        v-if="!isCancelled"
        class="shrink-0 font-display font-extrabold tabular-nums"
        :class="isCompleted ? 'text-success' : 'text-accent'"
      >
        {{ price }}
      </p>
    </div>

    <p v-if="isCompleted" class="mt-3 flex items-center gap-1.5 text-sm font-bold text-success">
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
        <path d="m5 12.5 4.5 4.5L19 7.5" />
      </svg>
      {{ t('dashboard.agenda.completed') }}
    </p>
    <p v-else-if="isCancelled" class="mt-3 text-sm font-bold text-muted">{{ t('dashboard.agenda.cancelled') }}</p>
    <BaseButton
      v-else
      class="mt-4"
      variant="primary"
      elevated
      block
      :disabled="isBusy"
      :aria-label="t('dashboard.agenda.completeFor', { client: request.clientName, time: request.time })"
      @click="emit('complete', request.id)"
    >
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
        <path d="m5 12.5 4.5 4.5L19 7.5" />
      </svg>
      {{ t('dashboard.agenda.complete') }}
    </BaseButton>
    <a
      v-if="!isDone"
      :href="remindUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="mt-2 flex h-11 items-center justify-center gap-2 rounded-full bg-raised text-sm font-bold ring-1 ring-line"
      :aria-label="t('dashboard.agenda.remindFor', { client: request.clientName })"
    >
      {{ t('dashboard.agenda.remind') }}
    </a>
  </article>
</template>
