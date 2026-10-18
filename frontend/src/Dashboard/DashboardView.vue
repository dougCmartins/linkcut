<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import MetricCard from './MetricCard.vue'
import PendingRequestCard from './PendingRequestCard.vue'
import { useDashboardStore } from './dashboard.store'
import { HEALTHY_MARGIN_PERCENT } from './financialSummary'
import { useBookingStore } from '@/Booking/booking.store'
import { barberProfile } from '@/mocks/barber'
import { formatBRL } from '@/utils/currency'

const { t } = useI18n()
const dashboardStore = useDashboardStore()
const bookingStore = useBookingStore()
const profile = barberProfile

const firstName = computed(() => profile.name.split(' ')[0] ?? profile.name)
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
    <header class="flex items-center gap-3 pr-28">
      <AvatarImage :src="profile.avatarUrl" :alt="t('bioLink.avatarAlt', { name: profile.name })" size="md" />
      <div>
        <h1 class="text-xl leading-tight">{{ t('dashboard.greeting', { name: firstName }) }}</h1>
        <RouterLink to="/" class="text-xs font-semibold text-accent">{{ t('dashboard.openPublicPage') }} →</RouterLink>
      </div>
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
