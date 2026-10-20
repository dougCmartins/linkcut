<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { BookingRequest } from '@/Domain/Schedule/booking.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { formatBRL } from '@/utils/currency'

/** `priceInCents`: the service's current price (shown until it is concluded with its own). */
const props = defineProps<{ request: BookingRequest; priceInCents: number | undefined; isBusy: boolean }>()
const emit = defineEmits<{ complete: [requestId: number] }>()
const { t } = useI18n()

const isCompleted = computed(() => props.request.status === AppointmentStatus.Completed)
const price = computed(() => formatBRL(props.request.completedPriceInCents ?? props.priceInCents ?? 0))
</script>

<template>
  <article
    class="rounded-card p-4 ring-1 transition"
    :class="isCompleted ? 'bg-raised/60 ring-line' : 'bg-surface ring-line shadow-soft'"
  >
    <div class="flex items-center gap-3">
      <span
        class="flex h-12 w-14 shrink-0 items-center justify-center rounded-2xl font-display text-sm font-extrabold tabular-nums"
        :class="isCompleted ? 'bg-success/15 text-success' : 'bg-raised text-ink'"
      >
        {{ request.time }}
      </span>
      <div class="min-w-0 flex-1">
        <h3 class="truncate text-base" :class="isCompleted ? 'text-muted' : ''">{{ request.clientName }}</h3>
        <p class="truncate text-sm text-muted">{{ request.serviceName }}</p>
      </div>
      <p class="shrink-0 font-display font-extrabold tabular-nums" :class="isCompleted ? 'text-success' : 'text-accent'">
        {{ price }}
      </p>
    </div>

    <p v-if="isCompleted" class="mt-3 flex items-center gap-1.5 text-sm font-bold text-success">
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
        <path d="m5 12.5 4.5 4.5L19 7.5" />
      </svg>
      {{ t('dashboard.agenda.completed') }}
    </p>
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
  </article>
</template>
