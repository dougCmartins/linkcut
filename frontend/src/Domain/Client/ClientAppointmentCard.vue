<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { formatDayMonth, fromIsoDate } from '@/utils/date'
import type { ClientAppointment } from './client.model'

const props = defineProps<{
  appointment: ClientAppointment
  isUpcoming: boolean
}>()
const { t } = useI18n()

const STATUS_CLASSES: Record<AppointmentStatus, string> = {
  [AppointmentStatus.Pending]: 'bg-raised text-ink',
  [AppointmentStatus.Confirmed]: 'bg-success/15 text-success',
  [AppointmentStatus.Completed]: 'bg-raised text-muted',
  [AppointmentStatus.Rejected]: 'bg-danger/10 text-danger',
}
const when = computed(() =>
  t('client.appointments.when', {
    date: formatDayMonth(fromIsoDate(props.appointment.date)),
    time: props.appointment.time,
  }),
)
const linkClass = 'inline-flex items-center gap-1 text-sm font-bold text-accent'
</script>

<template>
  <article class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="truncate text-base">{{ appointment.serviceName }}</h3>
        <p class="text-sm font-semibold tabular-nums">{{ when }}</p>
        <p class="truncate text-sm text-muted">
          {{ appointment.barbershop.name }}
        </p>
      </div>
      <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold" :class="STATUS_CLASSES[appointment.status]">
        {{ t(`client.appointments.status.${appointment.status}`) }}
      </span>
    </div>
    <p v-if="isUpcoming && appointment.barbershop.address" class="mt-3 text-sm break-words">
      {{ appointment.barbershop.address }}
    </p>
    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
      <template v-if="isUpcoming">
        <a
          v-if="appointment.barbershop.mapsUrl"
          :href="appointment.barbershop.mapsUrl"
          target="_blank"
          rel="noopener noreferrer"
          :class="linkClass"
        >
          {{ t('booking.directions') }} →
        </a>
        <a
          v-if="appointment.barbershop.whatsappUrl"
          :href="appointment.barbershop.whatsappUrl"
          target="_blank"
          rel="noopener noreferrer"
          :class="linkClass"
        >
          {{ t('client.appointments.whatsapp') }}
        </a>
      </template>
      <RouterLink v-else :to="{ name: 'booking', params: { slug: appointment.barbershop.slug } }" :class="linkClass">
        {{ t('client.appointments.bookAgain') }} →
      </RouterLink>
    </div>
  </article>
</template>
