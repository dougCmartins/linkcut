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
import { createBookingRequest, fetchAvailability } from './booking.api'
import type { AvailableDay } from './booking.model'
import { isOnVacation, isScheduleLocked } from './availability'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { createEmptySettings } from '@/Domain/Settings/settings.defaults'
import { useApiErrors } from '@/composables/useApiErrors'
import { GeolocationStatus, useGeolocation } from '@/composables/useGeolocation'
import { hasAddress } from '@/Domain/Settings/settings.model'
import { distanceInMeters, formatAddress, formatDistance, mapsUrl } from '@/utils/address'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { formatDayMonth, fromIsoDate } from '@/utils/date'
import { buildWhatsappUrl, isValidBrazilianPhone, onlyDigits } from '@/utils/phone'

const MIN_NAME_LENGTH = 2

const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
const { hasErrorCode, messageKey } = useApiErrors()
const publicPageStore = usePublicPageStore()
const dialog = useTemplateRef<HTMLElement>('dialog')

// App.vue opens the modal only after the booking options loaded; the fallback just keeps the types simple.
const schedule = computed(() => publicPageStore.bookingOptions ?? createEmptySettings().schedule)
const services = computed(() => schedule.value.services)
const findService = (serviceId: string) => services.value.find((service) => service.id === serviceId)
/** Shown before booking, so clients from far away notice it. Without it the barbershop can't take requests. */
const barbershopAddress = computed(() => {
  const address = publicPageStore.page?.profile.address ?? null
  return hasAddress(address) ? address : null
})

// Optional: the client's location, only to tell the barber how far they are (the API keeps only the distance).
const geolocation = useGeolocation()
const clientDistance = computed(() => {
  const point = geolocation.point.value
  const address = barbershopAddress.value
  if (!point || address?.latitude == null || address.longitude == null) return null
  return distanceInMeters(point, { latitude: address.latitude, longitude: address.longitude })
})
const locationMessage = computed(() => {
  switch (geolocation.status.value) {
    case GeolocationStatus.Asking:
      return t('booking.location.asking')
    case GeolocationStatus.Denied:
      return t('booking.location.denied')
    case GeolocationStatus.Unavailable:
      return t('booking.location.unavailable')
    default:
      return clientDistance.value === null ? '' : t('booking.location.distance', { distance: formatDistance(clientDistance.value) })
  }
})

const whatsappUrl = computed(() => {
  const profile = publicPageStore.page?.profile
  return profile ? buildWhatsappUrl(profile.whatsappNumber, profile.whatsappMessage) : ''
})

const form = reactive({
  serviceId: null as string | null,
  date: null as string | null,
  time: null as string | null,
  clientName: '',
  clientWhatsapp: '',
})
const submittedSummary = ref<string | null>(null)
const isSubmitting = ref(false)
const submitError = ref('')

/** Free times of the booking window, computed by the API (its "today" is the barbershop's). */
const availability = ref<AvailableDay[]>([])
const isLoadingTimes = ref(false)
const availabilityFailed = ref(false)

async function loadAvailability(): Promise<void> {
  const slug = publicPageStore.slug
  if (!slug) return
  isLoadingTimes.value = true
  availabilityFailed.value = false
  try {
    availability.value = await fetchAvailability(slug, form.serviceId)
  } catch {
    availabilityFailed.value = true
  } finally {
    isLoadingTimes.value = false
  }
}

const days = computed(() => availability.value.map((day) => fromIsoDate(day.date)))
const isLocked = computed(() => isScheduleLocked(days.value, schedule.value.vacation))
const unavailableDates = computed(() => availability.value.filter((day) => day.times.length === 0).map((day) => day.date))
const times = computed(() => availability.value.find((day) => day.date === form.date)?.times ?? [])
const timeEmptyText = computed(() => {
  if (isLoadingTimes.value) return t('booking.loadingTimes')
  if (availabilityFailed.value) return t('booking.timesFailed')
  if (!form.date) return t('booking.pickDayFirst')
  return isOnVacation(fromIsoDate(form.date), schedule.value.vacation) ? schedule.value.vacation.message : t('booking.noTimes')
})

const canSubmit = computed(
  () =>
    form.serviceId !== null &&
    form.date !== null &&
    form.time !== null &&
    form.clientName.trim().length >= MIN_NAME_LENGTH &&
    isValidBrazilianPhone(form.clientWhatsapp) &&
    !isSubmitting.value,
)

// The free times depend on the length of the chosen service.
watch(() => form.serviceId, loadAvailability)
// A time picked earlier may not fit after changing the day or the service.
watch(times, (available) => {
  if (form.time && !available.includes(form.time)) form.time = null
})

async function submit(): Promise<void> {
  const slug = publicPageStore.slug
  if (!canSubmit.value || !slug || !form.serviceId || !form.date || !form.time) return
  isSubmitting.value = true
  submitError.value = ''
  try {
    const confirmation = await createBookingRequest(slug, {
      serviceId: form.serviceId,
      date: form.date,
      time: form.time,
      clientName: form.clientName.trim(),
      clientWhatsapp: onlyDigits(form.clientWhatsapp),
      clientLocation: geolocation.point.value,
    })
    submittedSummary.value = t('booking.summary', {
      service: confirmation.serviceName || (findService(form.serviceId)?.name ?? ''),
      date: formatDayMonth(fromIsoDate(confirmation.date)),
      time: confirmation.time,
    })
  } catch (caught) {
    if (hasErrorCode(caught, ApiErrorCode.SlotUnavailable)) {
      // Someone took this time a moment ago: show the times that are still free.
      submitError.value = t('booking.slotTaken')
      form.time = null
      await loadAvailability()
    } else {
      submitError.value = t(`errors.api.${messageKey(caught)}`)
    }
  } finally {
    isSubmitting.value = false
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') emit('close')
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', onKeydown)
  dialog.value?.focus()
  void loadAvailability()
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
        v-else-if="!barbershopAddress"
        class="px-5 pb-8"
        :title="t('booking.noAddressTitle')"
        :message="t('booking.noAddress')"
        :whatsapp-url="whatsappUrl"
        @close="emit('close')"
      />

      <BookingVacationNotice
        v-else-if="isLocked"
        class="px-5 pb-8"
        :message="schedule.vacation.message"
        :whatsapp-url="whatsappUrl"
        @close="emit('close')"
      />

      <form v-else class="flex min-h-0 flex-1 flex-col" @submit.prevent="submit">
        <div class="flex-1 space-y-7 overflow-x-hidden overflow-y-auto overscroll-contain px-5 pt-2 pb-6">
          <section class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft" aria-labelledby="booking-where">
            <h3 id="booking-where" class="text-xs font-bold tracking-wide text-muted uppercase">{{ t('booking.whereTitle') }}</h3>
            <p class="mt-1.5 text-[15px] leading-snug font-semibold break-words">{{ formatAddress(barbershopAddress) }}</p>
            <a
              :href="mapsUrl(barbershopAddress)"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-accent"
            >
              {{ t('booking.directions') }} →
            </a>
            <div class="mt-4 border-t border-line pt-4">
              <p class="text-sm font-semibold">{{ t('booking.location.title') }}</p>
              <p v-if="locationMessage" class="mt-1.5 text-sm" role="status">{{ locationMessage }}</p>
              <BaseButton
                v-if="geolocation.status.value === GeolocationStatus.Idle"
                class="mt-3"
                variant="secondary"
                block
                @click="geolocation.request()"
              >
                {{ t('booking.location.share') }}
              </BaseButton>
              <p class="mt-2 text-xs text-muted">{{ t('booking.location.privacy') }}</p>
            </div>
          </section>

          <fieldset class="min-w-0">
            <legend class="mb-3 text-sm font-bold">{{ t('booking.stepService') }}</legend>
            <div role="radiogroup" :aria-label="t('booking.stepService')" class="grid gap-3">
              <ServiceCard
                v-for="service in services"
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
          <p v-if="submitError" class="mb-3 text-sm font-semibold text-danger" role="alert">{{ submitError }}</p>
          <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canSubmit">
            {{ t('booking.submit') }}
          </BaseButton>
        </div>
      </form>
    </section>
  </div>
</template>
