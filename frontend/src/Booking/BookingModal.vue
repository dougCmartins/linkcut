<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import ServiceCard from './ServiceCard.vue'
import DayPicker from './DayPicker.vue'
import TimeSlotPicker from './TimeSlotPicker.vue'
import BookingForm from './BookingForm.vue'
import BookingSuccess from './BookingSuccess.vue'
import BookingVacationNotice from './BookingVacationNotice.vue'
import { useBookingStore } from './booking.store'
import { computeAvailableTimes, isOnVacation, isScheduleLocked } from './availability'
import { useSettingsStore } from '@/Settings/settings.store'
import { formatDayMonth, fromIsoDate, nextDays, toIsoDate } from '@/utils/date'
import { buildWhatsappUrl, isValidBrazilianPhone, onlyDigits } from '@/utils/phone'

const MIN_NAME_LENGTH = 2

const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
const store = useBookingStore()
const settingsStore = useSettingsStore()
const dialog = useTemplateRef<HTMLElement>('dialog')

const now = new Date()
const schedule = computed(() => settingsStore.schedule)
const days = computed(() => nextDays(schedule.value.bookingWindowDays, now))
const isLocked = computed(() => isScheduleLocked(days.value, schedule.value.vacation))
const whatsappUrl = computed(() =>
  buildWhatsappUrl(settingsStore.profile.whatsappNumber, settingsStore.profile.whatsappMessage),
)

const form = reactive({
  serviceId: null as string | null,
  date: null as string | null,
  time: null as string | null,
  clientName: '',
  clientWhatsapp: '',
})
const submittedSummary = ref<string | null>(null)

/** Length used to find free times: the chosen service, or the shortest one before choosing. */
const durationMinutes = computed(() => {
  const selected = form.serviceId ? store.findService(form.serviceId) : undefined
  return selected?.durationMinutes ?? Math.min(...store.services.map((service) => service.durationMinutes))
})

function timesOn(date: Date): string[] {
  return computeAvailableTimes({
    date,
    schedule: schedule.value,
    durationMinutes: durationMinutes.value,
    booked: store.bookedSlots,
    now,
  })
}

const unavailableDates = computed(() => days.value.filter((day) => timesOn(day).length === 0).map(toIsoDate))
const times = computed(() => (form.date ? timesOn(fromIsoDate(form.date)) : []))
const timeEmptyText = computed(() => {
  if (!form.date) return t('booking.pickDayFirst')
  return isOnVacation(fromIsoDate(form.date), schedule.value.vacation) ? schedule.value.vacation.message : t('booking.noTimes')
})

const canSubmit = computed(
  () =>
    form.serviceId !== null &&
    form.date !== null &&
    form.time !== null &&
    form.clientName.trim().length >= MIN_NAME_LENGTH &&
    isValidBrazilianPhone(form.clientWhatsapp),
)

// A time picked earlier may not fit after changing the day or the service.
watch(times, (available) => {
  if (form.time && !available.includes(form.time)) form.time = null
})

function submit(): void {
  if (!canSubmit.value || !form.serviceId || !form.date || !form.time) return
  const request = store.submitRequest({
    serviceId: form.serviceId,
    date: form.date,
    time: form.time,
    clientName: form.clientName.trim(),
    clientWhatsapp: onlyDigits(form.clientWhatsapp),
  })
  submittedSummary.value = t('booking.summary', {
    service: store.findService(request.serviceId)?.name ?? '',
    date: formatDayMonth(fromIsoDate(request.date)),
    time: request.time,
  })
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') emit('close')
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', onKeydown)
  dialog.value?.focus()
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" aria-hidden="true" @click="emit('close')" />

    <section
      ref="dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="booking-title"
      tabindex="-1"
      class="relative flex max-h-[92dvh] w-full max-w-md flex-col overflow-hidden rounded-t-[28px] bg-page shadow-float ring-1 ring-line outline-none sm:rounded-[28px]"
    >
      <span class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-line sm:hidden" aria-hidden="true" />
      <header class="flex items-center justify-between px-5 pt-4 pb-2">
        <h2 id="booking-title" class="text-2xl">{{ t('booking.title') }}</h2>
        <button
          type="button"
          :aria-label="t('booking.close')"
          class="flex size-10 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft"
          @click="emit('close')"
        >
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </header>

      <BookingSuccess v-if="submittedSummary" class="px-5 pb-8" :summary="submittedSummary" @done="emit('close')" />

      <BookingVacationNotice
        v-else-if="isLocked"
        class="px-5 pb-8"
        :message="schedule.vacation.message"
        :whatsapp-url="whatsappUrl"
        @close="emit('close')"
      />

      <form v-else class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
        <div class="flex-1 space-y-7 overflow-x-hidden overflow-y-auto overscroll-contain px-5 pt-2 pb-6">
          <fieldset class="min-w-0">
            <legend class="mb-3 text-sm font-bold">{{ t('booking.stepService') }}</legend>
            <div role="radiogroup" :aria-label="t('booking.stepService')" class="grid gap-3">
              <ServiceCard
                v-for="service in store.services"
                :key="service.id"
                :service="service"
                :selected="service.id === form.serviceId"
                @select="form.serviceId = $event"
              />
            </div>
          </fieldset>

          <fieldset class="min-w-0">
            <legend class="mb-1 text-sm font-bold">{{ t('booking.stepDay') }}</legend>
            <DayPicker
              :days="days"
              :unavailable-dates="unavailableDates"
              :selected-date="form.date"
              :label="t('booking.stepDay')"
              @select="form.date = $event"
            />
          </fieldset>

          <fieldset class="min-w-0">
            <legend class="mb-1 text-sm font-bold">{{ t('booking.stepTime') }}</legend>
            <TimeSlotPicker
              :times="times"
              :selected-time="form.time"
              :label="t('booking.stepTime')"
              :empty-text="timeEmptyText"
              @select="form.time = $event"
            />
          </fieldset>

          <fieldset class="min-w-0">
            <legend class="mb-2 text-sm font-bold">{{ t('booking.stepContact') }}</legend>
            <BookingForm v-model:client-name="form.clientName" v-model:client-whatsapp="form.clientWhatsapp" />
          </fieldset>
        </div>

        <div class="border-t border-line bg-page px-5 pt-4 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
          <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canSubmit">
            {{ t('booking.submit') }}
          </BaseButton>
        </div>
      </form>
    </section>
  </div>
</template>
