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
import { formatBRL } from '@/utils/currency'
import { formatDuration } from '@/utils/time'
import { isValidEmail } from '@/utils/email'
import { useClientStore } from '@/Domain/Client/client.store'
import LegalDialog from '@/Legal/LegalDialog.vue'
import { useLegalOverlay } from '@/Legal/useLegalDocument'

const MIN_NAME_LENGTH = 2
/** Services in one visit (the API takes up to 5). */
const MAX_SERVICES = 5

const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
/** Terms and policy open over the form, so nothing typed is lost. */
const legal = useLegalOverlay()
const { hasErrorCode, messageKey } = useApiErrors()
const publicPageStore = usePublicPageStore()
const clientStore = useClientStore()
const dialog = useTemplateRef<HTMLElement>('dialog')

// App.vue opens the modal only after the booking options loaded; the fallback just keeps the types simple.
const schedule = computed(() => publicPageStore.bookingOptions ?? createEmptySettings().schedule)
const services = computed(() => schedule.value.services)
const findService = (serviceId: string) => services.value.find((service) => service.id === serviceId)
const chosenServices = computed(() => form.serviceIds.map(findService).filter((service) => service !== undefined))
/** "Total: 1h15 · R$ 90,00": times and prices of the chosen services add up. */
const totalLabel = computed(() => {
  if (chosenServices.value.length < 2) return ''
  const minutes = chosenServices.value.reduce((sum, service) => sum + service.durationMinutes, 0)
  const cents = chosenServices.value.reduce((sum, service) => sum + service.priceInCents, 0)
  return t('booking.total', { duration: formatDuration(minutes), price: formatBRL(cents) })
})

function toggleService(serviceId: string): void {
  if (form.serviceIds.includes(serviceId)) form.serviceIds = form.serviceIds.filter((id) => id !== serviceId)
  else if (form.serviceIds.length < MAX_SERVICES) form.serviceIds = [...form.serviceIds, serviceId]
}
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
/**
 * The location is asked on "Solicitar agendamento", in plain words, instead of a button easy to miss: only when the
 * barbershop is on the map and the client was not asked yet.
 */
const canAskLocation = computed(
  () => barbershopAddress.value?.latitude != null && barbershopAddress.value.longitude != null && 'geolocation' in navigator,
)
const isAskingLocation = ref(false)
const locationMessage = computed(() => {
  if (geolocation.status.value === GeolocationStatus.Asking) return t('booking.location.asking')
  return clientDistance.value === null ? '' : t('booking.location.distance', { distance: formatDistance(clientDistance.value) })
})

const whatsappUrl = computed(() => {
  const profile = publicPageStore.page?.profile
  return profile ? buildWhatsappUrl(profile.whatsappNumber, profile.whatsappMessage) : ''
})

const form = reactive({
  serviceIds: [] as string[],
  date: null as string | null,
  time: null as string | null,
  // A signed-in client (client area) books without typing their data again.
  clientName: clientStore.client?.name ?? '',
  clientWhatsapp: clientStore.client?.phone ?? '',
  clientEmail: clientStore.client?.email ?? '',
  clientNotes: '',
})
/** After booking, the e-mail for the "Finalizar cadastro" card (none when already signed in). */
const registerEmail = ref<string | null>(null)
const pushUrl = ref<string | null>(null)
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
    availability.value = await fetchAvailability(slug, form.serviceIds)
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
    form.serviceIds.length > 0 &&
    form.date !== null &&
    form.time !== null &&
    form.clientName.trim().length >= MIN_NAME_LENGTH &&
    isValidBrazilianPhone(form.clientWhatsapp) &&
    isValidEmail(form.clientEmail) &&
    !isSubmitting.value,
)

// The free times depend on the length of the chosen services together.
watch(() => form.serviceIds, loadAvailability)
// A time picked earlier may not fit after changing the day or the service.
watch(times, (available) => {
  if (form.time && !available.includes(form.time)) form.time = null
})

/** "Solicitar agendamento": first the location question (once), then the request. */
function onSubmit(): void {
  if (!canSubmit.value) return
  if (canAskLocation.value && geolocation.status.value === GeolocationStatus.Idle) {
    isAskingLocation.value = true
    return
  }
  void submit()
}

async function answerLocation(share: boolean): Promise<void> {
  isAskingLocation.value = false
  if (share) await geolocation.request()
  await submit()
}

async function submit(): Promise<void> {
  const slug = publicPageStore.slug
  if (!canSubmit.value || !slug || !form.date || !form.time) return
  isSubmitting.value = true
  submitError.value = ''
  try {
    const confirmation = await createBookingRequest(slug, {
      serviceIds: form.serviceIds,
      clientNotes: form.clientNotes,
      date: form.date,
      time: form.time,
      clientName: form.clientName.trim(),
      clientWhatsapp: onlyDigits(form.clientWhatsapp),
      clientEmail: form.clientEmail.trim(),
      clientLocation: geolocation.point.value,
    })
    registerEmail.value = clientStore.isSignedIn ? null : form.clientEmail.trim()
    pushUrl.value = confirmation.pushUrl
    submittedSummary.value = t('booking.summary', {
      service: confirmation.serviceName || chosenServices.value.map((service) => service.name).join(' + '),
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

/**
 * Enter in a text field goes to the next field instead of sending the request: on a phone the keyboard's
 * "Ir"/"Enter" would otherwise book before the client finished (the browser submits a form on Enter).
 */
function onFieldEnter(event: KeyboardEvent): void {
  const field = event.target
  if (!(field instanceof HTMLInputElement) || event.isComposing) return
  event.preventDefault()
  const form = field.form
  if (!form) return
  const fields = Array.from(form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input, textarea')).filter(
    (element) => !element.disabled && element.type !== 'hidden',
  )
  const next = fields[fields.indexOf(field) + 1]
  if (next) next.focus()
  else field.blur()
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

      <BookingSuccess
        v-if="submittedSummary"
        class="px-5 pb-8"
        :summary="submittedSummary"
        :register-email="registerEmail"
        :push-url="pushUrl"
        @done="emit('close')"
      />

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

      <form v-else class="flex min-h-0 flex-1 flex-col" @submit.prevent="onSubmit" @keydown.enter="onFieldEnter">
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
          </section>

          <fieldset class="min-w-0">
            <legend class="text-sm font-bold">{{ t('booking.stepService') }}</legend>
            <p class="mt-0.5 mb-3 text-xs text-muted">{{ t('booking.stepServiceHint') }}</p>
            <div role="group" :aria-label="t('booking.stepService')" class="grid gap-3">
              <ServiceCard
                v-for="service in services"
                :key="service.id"
                :service="service"
                :selected="form.serviceIds.includes(service.id)"
                @toggle="toggleService"
              />
            </div>
            <p v-if="totalLabel" class="mt-3 text-right font-display text-sm font-extrabold" role="status">{{ totalLabel }}</p>
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
            <BookingForm
              v-model:client-name="form.clientName"
              v-model:client-whatsapp="form.clientWhatsapp"
              v-model:client-email="form.clientEmail"
              v-model:client-notes="form.clientNotes"
            />
          </fieldset>
        </div>

        <div class="border-t border-line bg-page px-5 pt-4 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
          <p v-if="submitError" class="mb-3 text-sm font-semibold text-danger" role="alert">{{ submitError }}</p>
          <div v-if="isAskingLocation" class="grid gap-2" role="group" aria-labelledby="booking-location-title">
            <p id="booking-location-title" class="text-sm font-bold">{{ t('booking.location.title') }}</p>
            <p class="text-xs text-muted">{{ t('booking.location.ask') }}</p>
            <BaseButton variant="primary" size="lg" elevated block @click="answerLocation(true)">{{
              t('booking.location.shareAndSend')
            }}</BaseButton>
            <BaseButton variant="secondary" block @click="answerLocation(false)">{{ t('booking.location.sendWithout') }}</BaseButton>
          </div>
          <template v-else>
            <p v-if="locationMessage" class="mb-2 text-center text-xs font-semibold" role="status">{{ locationMessage }}</p>
            <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canSubmit">
              {{ isSubmitting ? t('booking.sending') : t('booking.submit') }}
            </BaseButton>
          </template>
          <p class="mt-2 text-center text-xs text-muted">
            {{ t('privacy.notice.booking') }}
            <a href="/privacidade" class="font-semibold underline" @click.prevent="legal.open('privacy')">{{
              t('privacy.links.policy')
            }}</a>
          </p>
        </div>
      </form>
    </section>
    <LegalDialog v-if="legal.document.value" :document="legal.document.value" @close="legal.close()" />
  </div>
</template>
