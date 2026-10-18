<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import ServiceCard from './ServiceCard.vue'
import DayPicker from './DayPicker.vue'
import TimeSlotPicker from './TimeSlotPicker.vue'
import BookingForm from './BookingForm.vue'
import BookingSuccess from './BookingSuccess.vue'
import { useBookingStore } from './booking.store'
import { availableTimes } from '@/mocks/barber'
import { formatDayMonth, fromIsoDate, nextDays } from '@/utils/date'
import { isValidBrazilianPhone, onlyDigits } from '@/utils/phone'

const BOOKING_WINDOW_DAYS = 7
const MIN_NAME_LENGTH = 2

const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
const store = useBookingStore()
const dialog = useTemplateRef<HTMLElement>('dialog')

const days = nextDays(BOOKING_WINDOW_DAYS)
const form = reactive({
  serviceId: null as string | null,
  date: null as string | null,
  time: null as string | null,
  clientName: '',
  clientWhatsapp: '',
})
const submittedSummary = ref<string | null>(null)

const times = computed(() => (form.date ? availableTimes(fromIsoDate(form.date)) : []))
const timeEmptyText = computed(() => (form.date ? t('booking.noTimes') : t('booking.pickDayFirst')))

const canSubmit = computed(
  () =>
    form.serviceId !== null &&
    form.date !== null &&
    form.time !== null &&
    form.clientName.trim().length >= MIN_NAME_LENGTH &&
    isValidBrazilianPhone(form.clientWhatsapp),
)

// A time picked for one day may not exist on another.
watch(
  () => form.date,
  () => (form.time = null),
)

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

      <form v-else class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
        <div class="flex-1 space-y-7 overflow-y-auto px-5 pt-2 pb-6">
          <fieldset>
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

          <fieldset>
            <legend class="mb-1 text-sm font-bold">{{ t('booking.stepDay') }}</legend>
            <DayPicker :days="days" :selected-date="form.date" :label="t('booking.stepDay')" @select="form.date = $event" />
          </fieldset>

          <fieldset>
            <legend class="mb-1 text-sm font-bold">{{ t('booking.stepTime') }}</legend>
            <TimeSlotPicker
              :times="times"
              :selected-time="form.time"
              :label="t('booking.stepTime')"
              :empty-text="timeEmptyText"
              @select="form.time = $event"
            />
          </fieldset>

          <fieldset>
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
