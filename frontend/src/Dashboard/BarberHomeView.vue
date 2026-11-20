<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useLiveRefresh } from '@/composables/useLiveRefresh'
import { capitalize, formatLongDate } from '@/utils/date'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { loginLocation } from '@/Domain/Identity/auth.guard'
import { useAgendaStore } from '@/Domain/Schedule/agenda.store'
import { lastBarberSlug, useTenantStore } from '@/Domain/Tenant/tenant.store'
import PendingRequestCard from './PendingRequestCard.vue'
import TodayAgendaCard from './TodayAgendaCard.vue'

/**
 * The dashboard of a barber of the team (docs/decisoes/0001 P3): the day's agenda and the requests to answer, nothing
 * of finance or settings (the owner's). Values show only when the owner allowed them: otherwise the API sends none.
 */
const { t } = useI18n()
const { messageKey } = useApiErrors()
const router = useRouter()
const authStore = useAuthStore()
const tenantStore = useTenantStore()
const agendaStore = useAgendaStore()

const firstName = computed(() => (authStore.user?.name ?? '').trim().split(' ')[0] ?? '')
const todayLabel = capitalize(formatLongDate(new Date()))
const busyRequestId = ref<number | null>(null)
const errorMessage = ref('')

onMounted(() => {
  void agendaStore.load()
  void tenantStore.ensureLoaded()
})
useLiveRefresh(() => agendaStore.load({ silent: true }), { isBusy: () => busyRequestId.value !== null })

async function attempt(requestId: number, change: () => Promise<unknown>): Promise<void> {
  busyRequestId.value = requestId
  errorMessage.value = ''
  try {
    await change()
  } catch (caught) {
    errorMessage.value = t(`errors.api.${messageKey(caught)}`)
    await agendaStore.load()
  } finally {
    busyRequestId.value = null
  }
}

async function logout(): Promise<void> {
  const slug = tenantStore.tenant?.slug ?? lastBarberSlug()
  await authStore.logout()
  await router.replace(loginLocation(undefined, slug))
}
</script>

<template>
  <main class="mx-auto w-full max-w-md px-4 pt-6 pb-12">
    <header class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold text-muted">{{ todayLabel }}</p>
        <h1 class="text-xl leading-tight break-words">{{ t('barberHome.greeting', { name: firstName }) }}</h1>
      </div>
      <BaseButton variant="secondary" @click="logout">{{ t('barberHome.logout') }}</BaseButton>
    </header>

    <p v-if="errorMessage" class="mt-4 text-sm text-danger" role="alert">{{ errorMessage }}</p>

    <section v-if="agendaStore.pendingRequests.length" class="mt-6">
      <h2 class="text-lg">{{ t('barberHome.pending') }}</h2>
      <ul class="mt-3 grid gap-3">
        <li v-for="request in agendaStore.pendingRequests" :key="request.id">
          <PendingRequestCard
            :request="request"
            :price-in-cents="request.priceInCents ?? undefined"
            @accept="(id: number) => attempt(id, () => agendaStore.accept(id))"
            @reject="(id: number) => attempt(id, () => agendaStore.reject(id))"
          />
        </li>
      </ul>
    </section>

    <section class="mt-6">
      <h2 class="text-lg">{{ t('barberHome.today') }}</h2>
      <p v-if="!agendaStore.todayAgenda.length" class="mt-3 text-sm text-muted">{{ t('barberHome.empty') }}</p>
      <ul v-else class="mt-3 grid gap-3">
        <li v-for="request in agendaStore.todayAgenda" :key="request.id">
          <TodayAgendaCard
            :request="request"
            :price-in-cents="request.priceInCents ?? undefined"
            :is-busy="busyRequestId === request.id"
            @complete="(id: number) => attempt(id, () => agendaStore.complete(id))"
          />
        </li>
      </ul>
    </section>
  </main>
</template>
