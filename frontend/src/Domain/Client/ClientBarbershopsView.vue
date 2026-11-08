<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BarbershopLogo from './BarbershopLogo.vue'
import ClientLayout from './ClientLayout.vue'
import { CLIENT_APPOINTMENTS_ROUTE } from './client.guard'
import type { BarbershopContact } from './client.model'
import { useClientStore } from './client.store'

/**
 * /account: every barbershop where the client booked (newest visit first). "Abrir" goes to its client area
 * (/<slug>/account), with its theme. Built from the appointments: no list of other barbershops (no marketplace).
 */
const { t } = useI18n()
const clientStore = useClientStore()

const isLoading = ref(!clientStore.appointmentsLoaded)
const failed = ref(false)

const barbershops = computed(() => {
  const bySlug = new Map<string, { barbershop: BarbershopContact; visits: number }>()
  for (const appointment of clientStore.appointments) {
    const entry = bySlug.get(appointment.barbershop.slug)
    if (entry) entry.visits += 1
    else bySlug.set(appointment.barbershop.slug, { barbershop: appointment.barbershop, visits: 1 })
  }
  return [...bySlug.values()]
})

onMounted(async () => {
  try {
    await clientStore.ensureAppointments()
  } catch {
    failed.value = true
  } finally {
    isLoading.value = false
  }
})

const linkClass = 'inline-flex items-center gap-1 text-sm font-bold text-accent'
const openClass =
  'inline-flex h-11 items-center justify-center rounded-button bg-accent px-5 font-display text-sm font-bold text-on-accent shadow-soft transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent'
</script>

<template>
  <ClientLayout :title="t('client.barbershops.title')">
    <p class="-mt-2 mb-4 text-sm text-muted">{{ t('client.barbershops.subtitle') }}</p>
    <p v-if="isLoading" class="text-sm text-muted" role="status">{{ t('client.appointments.loading') }}</p>
    <p v-else-if="failed" class="text-sm font-semibold text-danger" role="alert">{{ t('client.appointments.failed') }}</p>
    <p v-else-if="barbershops.length === 0" class="text-sm text-muted">{{ t('client.barbershops.empty') }}</p>
    <ul v-else class="grid gap-3">
      <li v-for="{ barbershop, visits } in barbershops" :key="barbershop.slug">
        <article class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
          <div class="flex items-center gap-3">
            <BarbershopLogo :name="barbershop.name" :src="barbershop.avatarUrl" size="md" />
            <div class="min-w-0 flex-1">
              <h2 class="text-base break-words">{{ barbershop.name }}</h2>
              <p class="text-xs text-muted">{{ t('client.barbershops.visits', { count: visits }, visits) }}</p>
            </div>
          </div>
          <p v-if="barbershop.address" class="mt-3 text-sm text-muted break-words">{{ barbershop.address }}</p>
          <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-3">
            <RouterLink :to="{ name: CLIENT_APPOINTMENTS_ROUTE, params: { slug: barbershop.slug } }" :class="openClass">
              {{ t('client.barbershops.open') }}
            </RouterLink>
            <RouterLink :to="{ name: 'bio-link', params: { slug: barbershop.slug } }" :class="linkClass">
              {{ t('client.barbershops.page') }}
            </RouterLink>
          </div>
        </article>
      </li>
    </ul>
  </ClientLayout>
</template>
