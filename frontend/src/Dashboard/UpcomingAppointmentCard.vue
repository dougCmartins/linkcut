<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { BookingRequest } from '@/Domain/Schedule/booking.model'
import { capitalize, formatWeekdayShort, fromIsoDate } from '@/utils/date'
import { formatBRL } from '@/utils/currency'
import { buildWhatsappUrl } from '@/utils/phone'
import ClientNote from './ClientNote.vue'

/** A confirmed appointment of a next day: when, who, what, and a one-tap reminder on WhatsApp. */
const props = withDefaults(defineProps<{ request: BookingRequest; priceInCents: number | undefined; barbershopName?: string }>(), {
  barbershopName: '',
})
const { t } = useI18n()

const day = computed(() => {
  const date = fromIsoDate(props.request.date)
  const dayMonth = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit' }).format(date)
  return `${capitalize(formatWeekdayShort(date))}, ${dayMonth}`
})
const remindUrl = computed(() => {
  const firstName = props.request.clientName.trim().split(' ')[0]
  const message = t('dashboard.upcoming.remindMessage', {
    client: firstName,
    day: day.value,
    time: props.request.time,
    barbershop: props.barbershopName,
  })
  return buildWhatsappUrl(props.request.clientWhatsapp, message)
})
</script>

<template>
  <article class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
    <div class="flex items-center gap-3">
      <span class="flex w-16 shrink-0 flex-col items-center rounded-2xl bg-raised py-2 text-center">
        <span class="text-[11px] font-bold text-muted">{{ day }}</span>
        <span class="font-display text-sm font-extrabold tabular-nums">{{ request.time }}</span>
      </span>
      <div class="min-w-0 flex-1">
        <h3 class="text-base break-words">{{ request.clientName }}</h3>
        <p class="text-sm text-muted break-words">{{ request.serviceName }}</p>
        <a
          :href="remindUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-xs font-bold text-accent"
          :aria-label="t('dashboard.agenda.remindFor', { client: request.clientName })"
        >
          {{ t('dashboard.agenda.remind') }}
        </a>
      </div>
      <p class="shrink-0 font-display font-extrabold tabular-nums text-accent">{{ formatBRL(priceInCents ?? 0) }}</p>
    </div>
    <ClientNote :note="request.clientNotes" />
  </article>
</template>
