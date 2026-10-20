<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { toIsoDate } from '@/utils/date'
import ClientAppointmentCard from './ClientAppointmentCard.vue'
import { fetchAppointments } from './client.api'
import type { ClientAppointment } from './client.model'
import { useClientStore } from './client.store'

/**
 * /conta: the client's appointments at every barbershop where they booked. Upcoming ones (waiting or confirmed)
 * show where to go; past ones offer "Agendar de novo". No list of other barbershops (no marketplace).
 */
const { t } = useI18n()
const router = useRouter()
const clientStore = useClientStore()

const appointments = ref<ClientAppointment[]>([])
const isLoading = ref(true)
const failed = ref(false)

const today = toIsoDate(new Date())
const isUpcoming = (appointment: ClientAppointment) =>
  appointment.date >= today && (appointment.status === AppointmentStatus.Pending || appointment.status === AppointmentStatus.Confirmed)
const upcoming = computed(() => appointments.value.filter(isUpcoming).reverse())
const past = computed(() => appointments.value.filter((appointment) => !isUpcoming(appointment)))
const firstName = computed(() => clientStore.client?.name.trim().split(' ')[0] ?? '')

async function logout(): Promise<void> {
  await clientStore.logout()
  await router.replace({ name: 'client-login' })
}

onMounted(async () => {
  try {
    appointments.value = await fetchAppointments()
  } catch {
    failed.value = true
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <main class="mx-auto w-full max-w-md px-5 py-8">
    <header class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-sm text-muted">
          {{ t('client.appointments.greeting', { name: firstName }) }}
        </p>
        <h1 class="text-2xl">{{ t('client.appointments.title') }}</h1>
      </div>
      <button type="button" class="shrink-0 text-sm font-semibold text-muted hover:text-ink" @click="logout">
        {{ t('auth.logout') }}
      </button>
    </header>

    <p v-if="isLoading" class="mt-8 text-sm text-muted" role="status">
      {{ t('client.appointments.loading') }}
    </p>
    <p v-else-if="failed" class="mt-8 text-sm font-semibold text-danger" role="alert">
      {{ t('client.appointments.failed') }}
    </p>
    <template v-else>
      <section class="mt-8" aria-labelledby="upcoming-title">
        <h2 id="upcoming-title" class="mb-3 text-sm font-bold">
          {{ t('client.appointments.upcoming') }}
        </h2>
        <p v-if="upcoming.length === 0" class="text-sm text-muted">
          {{ t('client.appointments.noUpcoming') }}
        </p>
        <div class="grid gap-3">
          <ClientAppointmentCard v-for="item in upcoming" :key="item.id" :appointment="item" is-upcoming />
        </div>
      </section>
      <section v-if="past.length" class="mt-8" aria-labelledby="past-title">
        <h2 id="past-title" class="mb-3 text-sm font-bold">
          {{ t('client.appointments.past') }}
        </h2>
        <div class="grid gap-3">
          <ClientAppointmentCard v-for="item in past" :key="item.id" :appointment="item" :is-upcoming="false" />
        </div>
      </section>
    </template>
  </main>
</template>
