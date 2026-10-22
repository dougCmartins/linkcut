<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useApiErrors } from '@/composables/useApiErrors'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { toIsoDate } from '@/utils/date'
import ClientAppointmentCard from './ClientAppointmentCard.vue'
import ClientLayout from './ClientLayout.vue'
import type { ClientAppointment } from './client.model'
import { useClientStore } from './client.store'

/**
 * /conta: the client's appointments at every barbershop where they booked, each with its logo. Upcoming ones
 * (waiting or confirmed) show where to go and can be cancelled; past ones offer "Agendar de novo".
 */
const { t } = useI18n()
const apiErrors = useApiErrors()
const clientStore = useClientStore()

const isLoading = ref(!clientStore.appointmentsLoaded)
const failed = ref(false)
const cancellingId = ref<number | null>(null)
const cancelError = ref<{ id: number; message: string } | null>(null)

const today = toIsoDate(new Date())
const isUpcoming = (appointment: ClientAppointment) =>
  appointment.date >= today && (appointment.status === AppointmentStatus.Pending || appointment.status === AppointmentStatus.Confirmed)
const upcoming = computed(() => clientStore.appointments.filter(isUpcoming).reverse())
const past = computed(() => clientStore.appointments.filter((appointment) => !isUpcoming(appointment)))

async function cancel(id: number): Promise<void> {
  cancellingId.value = id
  cancelError.value = null
  try {
    await clientStore.cancelAppointment(id)
  } catch (caught) {
    cancelError.value = { id, message: t(`errors.api.${apiErrors.messageKey(caught)}`) }
  } finally {
    cancellingId.value = null
  }
}

onMounted(async () => {
  try {
    await clientStore.ensureAppointments()
  } catch {
    failed.value = true
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <ClientLayout :title="t('client.appointments.title')">
    <p v-if="isLoading" class="text-sm text-muted" role="status">{{ t('client.appointments.loading') }}</p>
    <p v-else-if="failed" class="text-sm font-semibold text-danger" role="alert">{{ t('client.appointments.failed') }}</p>
    <template v-else>
      <section aria-labelledby="upcoming-title">
        <h2 id="upcoming-title" class="mb-3 text-sm font-bold">{{ t('client.appointments.upcoming') }}</h2>
        <p v-if="upcoming.length === 0" class="text-sm text-muted">{{ t('client.appointments.noUpcoming') }}</p>
        <div class="grid gap-3">
          <ClientAppointmentCard
            v-for="item in upcoming"
            :key="item.id"
            :appointment="item"
            is-upcoming
            :is-cancelling="cancellingId === item.id"
            :error="cancelError?.id === item.id ? cancelError.message : ''"
            @cancel="cancel"
          />
        </div>
      </section>
      <section v-if="past.length" class="mt-8" aria-labelledby="past-title">
        <h2 id="past-title" class="mb-3 text-sm font-bold">{{ t('client.appointments.past') }}</h2>
        <div class="grid gap-3">
          <ClientAppointmentCard v-for="item in past" :key="item.id" :appointment="item" :is-upcoming="false" />
        </div>
      </section>
    </template>
  </ClientLayout>
</template>
