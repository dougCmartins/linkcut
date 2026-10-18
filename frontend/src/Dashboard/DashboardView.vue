<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import MetricCard from './MetricCard.vue'
import PendingRequestCard from './PendingRequestCard.vue'
import { useDashboardStore } from './dashboard.store'
import { HEALTHY_MARGIN_PERCENT } from './financialSummary'
import { useBookingStore } from '@/Booking/booking.store'
import { useSettingsStore } from '@/Settings/settings.store'
import { useAuthStore } from '@/Auth/auth.store'
import { useRouter } from 'vue-router'
import { formatBRL } from '@/utils/currency'

const { t } = useI18n()
const dashboardStore = useDashboardStore()
const bookingStore = useBookingStore()
const settingsStore = useSettingsStore()

const authStore = useAuthStore()
const router = useRouter()

async function logout(): Promise<void> {
  await authStore.logout()
  await router.replace({ name: 'login' })
}

const profile = computed(() => settingsStore.profile)
const avatarUrl = computed(() => settingsStore.resolveImage(profile.value.avatar))
const firstName = computed(() => profile.value.name.trim().split(' ')[0] ?? profile.value.name)
const summary = computed(() => dashboardStore.summary)
const marginLabel = computed(() => `${summary.value.marginPercent.toFixed(1).replace('.', ',')}%`)
const marginBadge = computed(() =>
  summary.value.isBelowHealthyMargin
    ? t('dashboard.marginAlert', { threshold: HEALTHY_MARGIN_PERCENT })
    : t('dashboard.marginHealthy'),
)
</script>

<template>
  <main class="mx-auto w-full max-w-md px-4 pt-6 pb-12">
    <header class="flex items-center gap-3">
      <AvatarImage :src="avatarUrl" :alt="t('bioLink.avatarAlt', { name: profile.name })" size="md" />
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-xl leading-tight">{{ t('dashboard.greeting', { name: firstName }) }}</h1>
        <RouterLink to="/" class="text-xs font-semibold text-accent">{{ t('dashboard.openPublicPage') }} →</RouterLink>
      </div>
      <RouterLink
        to="/painel/configuracoes"
        :aria-label="t('dashboard.settings')"
        :title="t('dashboard.settings')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 hover:text-accent"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="3" />
          <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z" />
        </svg>
      </RouterLink>
      <button
        type="button"
        :aria-label="t('auth.logout')"
        :title="t('auth.logout')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 hover:text-danger"
        @click="logout"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
        </svg>
      </button>
    </header>

    <section class="mt-8" :aria-label="t('dashboard.period')">
      <h2 class="text-sm font-bold text-muted">{{ t('dashboard.period') }}</h2>
      <div class="mt-3 grid grid-cols-2 gap-3">
        <MetricCard class="col-span-2" tone="accent" :label="t('dashboard.revenue')" :value="formatBRL(summary.revenueInCents)" />
        <MetricCard :label="t('dashboard.profit')" :value="formatBRL(summary.profitInCents)" />
        <MetricCard
          :label="t('dashboard.margin')"
          :value="marginLabel"
          :badge="marginBadge"
          :tone="summary.isBelowHealthyMargin ? 'danger' : 'success'"
        />
      </div>
    </section>

    <section class="mt-10">
      <div class="flex items-center justify-between">
        <h2 class="text-xl">{{ t('dashboard.pendingTitle') }}</h2>
        <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-accent px-2 text-xs font-extrabold text-on-accent">
          {{ bookingStore.pendingRequests.length }}
        </span>
      </div>

      <TransitionGroup tag="ul" name="request" class="relative mt-4 grid gap-3">
        <li v-for="request in bookingStore.pendingRequests" :key="request.id">
          <PendingRequestCard
            :request="request"
            :service="bookingStore.findService(request.serviceId)"
            @accept="bookingStore.acceptRequest"
            @reject="bookingStore.rejectRequest"
          />
        </li>
      </TransitionGroup>
      <p
        v-if="bookingStore.pendingRequests.length === 0"
        class="mt-4 rounded-card bg-surface px-4 py-6 text-center text-sm text-muted ring-1 ring-line shadow-soft"
      >
        {{ t('dashboard.pendingEmpty') }}
      </p>
    </section>
  </main>
</template>

<style scoped>
.request-leave-active {
  transition: all 0.25s ease;
  position: absolute;
  width: 100%;
}
.request-leave-to {
  opacity: 0;
  transform: translateX(24px);
}
.request-move {
  transition: transform 0.25s ease;
}
</style>
