<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { capitalize, formatLongDate, formatMonthName, toIsoDate } from '@/utils/date'
import { useI18n } from 'vue-i18n'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import MetricCard from './MetricCard.vue'
import PendingRequestCard from './PendingRequestCard.vue'
import TodayAgendaCard from './TodayAgendaCard.vue'
import MarginExplanation from './MarginExplanation.vue'
import InfoTooltip from '@/components/ui/InfoTooltip.vue'
import UndoToast from '@/components/ui/UndoToast.vue'
import { useUndoWindow } from '@/composables/useUndoWindow'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { useDashboardStore } from './dashboard.store'
import { HEALTHY_MARGIN_PERCENT, formatMarginPercent } from './financialSummary'
import { useBookingStore } from '@/Booking/booking.store'
import { useSettingsStore } from '@/Settings/settings.store'
import { useAuthStore } from '@/Domain/Identity/auth.store'
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
const PLACEHOLDER = '—'
const today = new Date()
const todayLabel = capitalize(formatLongDate(today))
const periodTitle = t('dashboard.periodOf', { month: formatMonthName(today) })

const summary = computed(() => dashboardStore.summary)
const revenueLabel = computed(() => (summary.value ? formatBRL(summary.value.revenueInCents) : PLACEHOLDER))
const marginLabel = computed(() => (summary.value ? formatMarginPercent(summary.value.marginPercent) : PLACEHOLDER))
const expensesLabel = computed(() => (summary.value ? formatBRL(summary.value.expensesInCents) : PLACEHOLDER))
const marginBadge = computed(() => {
  if (!summary.value) return undefined
  return summary.value.isBelowHealthyMargin
    ? t('dashboard.marginAlert', { threshold: HEALTHY_MARGIN_PERCENT })
    : t('dashboard.marginHealthy')
})

onMounted(() => dashboardStore.loadMonthToDate(today))

// "Agenda de hoje": conclude in one click, with a few seconds to undo a mistaken tap.
const busyRequestId = ref<string | null>(null)
const lastCompleted = useUndoWindow<{ id: string; priceInCents: number }>()

const agenda = computed(() => bookingStore.todayAgenda)
const completedCount = computed(
  () => agenda.value.filter((request) => request.status === AppointmentStatus.Completed).length,
)

async function complete(requestId: string): Promise<void> {
  busyRequestId.value = requestId
  try {
    const request = await bookingStore.completeRequest(requestId)
    lastCompleted.offer({ id: request.id, priceInCents: request.completedPriceInCents ?? 0 })
    await dashboardStore.loadMonthToDate(new Date())
  } finally {
    busyRequestId.value = null
  }
}

async function undo(): Promise<void> {
  const completed = lastCompleted.take()
  if (!completed) return
  await bookingStore.undoCompletion(completed.id)
  await dashboardStore.loadMonthToDate(new Date())
}

</script>

<template>
  <main class="mx-auto w-full max-w-md px-4 pt-6 pb-12">
    <header class="flex items-center gap-3">
      <AvatarImage :src="avatarUrl" :alt="t('bioLink.avatarAlt', { name: profile.name })" size="md" />
      <div class="min-w-0 flex-1">
        <p class="truncate text-xs font-semibold text-muted">
          <time :datetime="toIsoDate(today)">{{ todayLabel }}</time>
        </p>
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

    <section class="mt-8" :aria-labelledby="'dashboard-period'">
      <div class="flex items-center justify-between gap-3">
        <h2 id="dashboard-period" class="text-sm font-bold text-muted">{{ periodTitle }}</h2>
        <span
          class="flex items-center gap-1 rounded-full bg-raised px-2.5 py-1 text-[11px] font-bold text-muted ring-1 ring-line"
          :title="t('dashboard.monthToDateInfo')"
        >
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5M12 8h.01" />
          </svg>
          {{ t('dashboard.monthToDate') }}
          <span class="sr-only">— {{ t('dashboard.monthToDateInfo') }}</span>
        </span>
      </div>
      <div class="mt-3 grid grid-cols-2 gap-3">
        <RouterLink
          to="/painel/historico"
          class="col-span-2 rounded-card transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          :aria-label="t('dashboard.revenueLink', { value: revenueLabel })"
        >
          <MetricCard tone="accent" :label="t('dashboard.revenue')" :value="revenueLabel" :hint="t('dashboard.seeHistory')" />
        </RouterLink>
        <RouterLink
          to="/painel/despesas"
          class="rounded-card transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          :aria-label="t('dashboard.expensesLink', { value: expensesLabel })"
        >
          <MetricCard class="h-full" :label="t('dashboard.expenses')" :value="expensesLabel" />
        </RouterLink>
        <MetricCard :label="t('dashboard.profit')" :value="summary ? formatBRL(summary.profitInCents) : PLACEHOLDER" />
        <MetricCard
          class="col-span-2"
          :label="t('dashboard.margin')"
          :value="marginLabel"
          :badge="marginBadge"
          :tone="summary?.isBelowHealthyMargin ? 'danger' : 'success'"
        >
          <template #info>
            <InfoTooltip :label="t('dashboard.marginInfo.label')">
              <MarginExplanation
                v-if="summary && dashboardStore.finances"
                :revenue-in-cents="summary.revenueInCents"
                :expenses="dashboardStore.finances.expenses"
                :profit-in-cents="summary.profitInCents"
                :margin-percent="summary.marginPercent"
              />
              <p v-else>{{ t('dashboard.marginInfo.formula') }}</p>
            </InfoTooltip>
          </template>
        </MetricCard>
      </div>
    </section>

    <section class="mt-10" aria-labelledby="today-agenda-title">
      <div class="flex items-center justify-between gap-3">
        <h2 id="today-agenda-title" class="text-xl">{{ t('dashboard.agenda.title') }}</h2>
        <span v-if="agenda.length" class="text-xs font-bold text-muted">
          {{ t('dashboard.agenda.progress', { done: completedCount, total: agenda.length }) }}
        </span>
      </div>
      <ul v-if="agenda.length" class="mt-4 grid gap-3">
        <li v-for="request in agenda" :key="request.id">
          <TodayAgendaCard
            :request="request"
            :service="bookingStore.findService(request.serviceId)"
            :is-busy="busyRequestId === request.id"
            @complete="complete"
          />
        </li>
      </ul>
      <p v-else class="mt-4 rounded-card bg-surface px-4 py-6 text-center text-sm text-muted ring-1 ring-line shadow-soft">
        {{ t('dashboard.agenda.empty') }}
      </p>
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
    <UndoToast
      v-if="lastCompleted.pending.value"
      :message="t('dashboard.agenda.completedToast', { value: formatBRL(lastCompleted.pending.value.priceInCents) })"
      :action-label="t('dashboard.agenda.undo')"
      @action="undo"
    />
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
