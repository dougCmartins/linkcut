<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { googleCalendarUrl, icsDataUrl, type CalendarEvent } from '@/utils/calendar'
import { formatDayMonth, fromIsoDate } from '@/utils/date'
import BarbershopLogo from './BarbershopLogo.vue'
import type { ClientAppointment } from './client.model'

/**
 * One appointment: the barbershop (logo and name) on top. Upcoming ones show where to go, the calendar links and
 * "Cancelar" (asks first); past ones offer "Agendar de novo".
 */
const props = withDefaults(
  defineProps<{
    appointment: ClientAppointment
    isUpcoming: boolean
    isCancelling?: boolean
    /** Why the last cancel failed (e.g. too close to the time). */
    error?: string
  }>(),
  { isCancelling: false, error: '' },
)
const emit = defineEmits<{ cancel: [id: number] }>()
const { t } = useI18n()

const isConfirming = ref(false)

const STATUS_CLASSES: Record<AppointmentStatus, string> = {
  [AppointmentStatus.Pending]: 'bg-raised text-ink',
  [AppointmentStatus.Confirmed]: 'bg-success/15 text-success',
  [AppointmentStatus.Completed]: 'bg-raised text-muted',
  [AppointmentStatus.Rejected]: 'bg-danger/10 text-danger',
  [AppointmentStatus.Cancelled]: 'bg-raised text-muted',
}
const when = computed(() =>
  t('client.appointments.when', {
    date: formatDayMonth(fromIsoDate(props.appointment.date)),
    time: props.appointment.time,
  }),
)
const calendarEvent = computed<CalendarEvent>(() => ({
  title: t('client.appointments.calendarTitle', { service: props.appointment.serviceName, barbershop: props.appointment.barbershop.name }),
  date: props.appointment.date,
  startTime: props.appointment.time,
  endTime: props.appointment.endTime,
  location: props.appointment.barbershop.address,
  details: props.appointment.barbershop.whatsappUrl,
}))
const googleUrl = computed(() => googleCalendarUrl(calendarEvent.value))
const icsUrl = computed(() => icsDataUrl(calendarEvent.value, `appointment-${props.appointment.id}@linkcut`))

function confirmCancel(): void {
  isConfirming.value = false
  emit('cancel', props.appointment.id)
}

const linkClass = 'inline-flex items-center gap-1 text-sm font-bold text-accent'
</script>

<template>
  <article class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
    <div class="flex items-center gap-3">
      <BarbershopLogo :name="appointment.barbershop.name" :src="appointment.barbershop.avatarUrl" />
      <p class="min-w-0 flex-1 font-display text-sm font-extrabold break-words">{{ appointment.barbershop.name }}</p>
      <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold" :class="STATUS_CLASSES[appointment.status]">
        {{ t(`client.appointments.status.${appointment.status}`) }}
      </span>
    </div>

    <h3 class="mt-3 text-base break-words">{{ appointment.serviceName }}</h3>
    <p class="text-sm font-semibold tabular-nums">{{ when }}</p>
    <p v-if="isUpcoming && appointment.barbershop.address" class="mt-1 text-sm text-muted break-words">
      {{ appointment.barbershop.address }}
    </p>

    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
      <template v-if="isUpcoming">
        <a v-if="appointment.barbershop.mapsUrl" :href="appointment.barbershop.mapsUrl" target="_blank" rel="noopener noreferrer" :class="linkClass">
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
      <RouterLink
        v-if="appointment.status === AppointmentStatus.Completed && !appointment.hasReview"
        :to="{ name: 'client-review', params: { appointment: String(appointment.id) } }"
        :class="linkClass"
      >
        {{ t('reviews.rate') }}
      </RouterLink>
    </div>

    <template v-if="isUpcoming">
      <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
        {{ t('client.appointments.addToCalendar') }}
        <a :href="googleUrl" target="_blank" rel="noopener noreferrer" class="font-bold text-ink underline">Google</a>
        <a :href="icsUrl" :download="`linkcut-${appointment.date}.ics`" class="font-bold text-ink underline">
          {{ t('client.appointments.otherCalendar') }}
        </a>
      </p>

      <div class="mt-4 border-t border-line pt-3">
        <div v-if="isConfirming" class="flex flex-wrap items-center gap-3" role="group" :aria-label="t('client.appointments.cancelQuestion')">
          <p class="w-full text-sm font-semibold">{{ t('client.appointments.cancelQuestion') }}</p>
          <button
            type="button"
            class="h-10 rounded-full bg-danger px-4 text-sm font-bold text-white disabled:opacity-60"
            :disabled="isCancelling"
            @click="confirmCancel"
          >
            {{ t('client.appointments.cancelConfirm') }}
          </button>
          <button type="button" class="h-10 px-2 text-sm font-semibold text-muted hover:text-ink" @click="isConfirming = false">
            {{ t('client.appointments.cancelKeep') }}
          </button>
        </div>
        <button
          v-else
          type="button"
          class="text-sm font-semibold text-muted hover:text-danger disabled:opacity-60"
          :disabled="isCancelling"
          @click="isConfirming = true"
        >
          {{ isCancelling ? t('client.appointments.cancelling') : t('client.appointments.cancel') }}
        </button>
        <p v-if="error" class="mt-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>
      </div>
    </template>
  </article>
</template>
