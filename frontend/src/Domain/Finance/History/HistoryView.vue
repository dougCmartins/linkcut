<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, shallowRef, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import MetricCard from '@/components/ui/MetricCard.vue'
import HistoryList from './HistoryList.vue'
import HistoryPeriodFilter from './HistoryPeriodFilter.vue'
import { fetchHistoryPage, requestHistoryReport } from './history.api'
import { HistoryPeriod, type CompletedService, type DateRange, type HistorySummary } from './history.model'
import { customRangeError, monthToDate, periodRange } from './historyPeriod'
import { groupByDay } from './historySummary'
import { formatBRL } from '@/utils/currency'
import { fromIsoDate, toIsoDate } from '@/utils/date'

const { t } = useI18n()
const today = new Date()
const todayIso = toIsoDate(today)

// The current month by default; longer periods (up to a year) through the filter.
const period = ref<HistoryPeriod>(HistoryPeriod.Month)
const customRange = ref<DateRange>(monthToDate(today))

const EMPTY_SUMMARY: HistorySummary = { totalInCents: 0, count: 0, averageTicketInCents: 0 }

/** Rows already loaded (newest first); the next slices are appended as the barber scrolls. Read only here. */
const records = shallowRef<CompletedService[]>([])
const summary = shallowRef<HistorySummary>(EMPTY_SUMMARY)
const nextCursor = ref<string | null>(null)
const state = reactive({
  isLoading: false,
  hasFailed: false,
  isLoadingMore: false,
  loadMoreFailed: false,
  isExporting: false,
  exportFailed: false,
  exportedTo: '',
})

const rangeErrorKey = computed(() => (period.value === HistoryPeriod.Custom ? customRangeError(customRange.value) : null))
const rangeError = computed(() => (rangeErrorKey.value ? t(`history.errors.${rangeErrorKey.value}`) : undefined))
const range = computed(() => periodRange(period.value, today, customRange.value))

const rangeFormatter = new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' })
const rangeLabel = computed(() =>
  rangeErrorKey.value ? '' : rangeFormatter.formatRange(fromIsoDate(range.value.from), fromIsoDate(range.value.to)),
)

const groups = computed(() => groupByDay(records.value))

/** Each new filter starts again; an answer to an older filter is dropped. */
let generation = 0

async function loadFirst(): Promise<void> {
  const current = ++generation
  nextCursor.value = null
  state.exportedTo = ''
  state.loadMoreFailed = false
  if (rangeErrorKey.value) {
    records.value = []
    summary.value = EMPTY_SUMMARY
    state.isLoading = false
    return
  }
  state.isLoading = true
  state.hasFailed = false
  try {
    const page = await fetchHistoryPage(range.value)
    if (current !== generation) return
    records.value = page.items
    summary.value = page.summary ?? EMPTY_SUMMARY
    nextCursor.value = page.nextCursor
  } catch {
    if (current === generation) state.hasFailed = true
  } finally {
    if (current === generation) state.isLoading = false
  }
}

/** The next slice, after the last row on screen (cursor): the same cost however far the barber scrolls. */
async function loadMore(): Promise<void> {
  if (!nextCursor.value || state.isLoadingMore || state.isLoading) return
  const current = generation
  state.isLoadingMore = true
  state.loadMoreFailed = false
  try {
    const page = await fetchHistoryPage(range.value, nextCursor.value)
    if (current !== generation) return
    records.value = [...records.value, ...page.items]
    nextCursor.value = page.nextCursor
  } catch {
    if (current === generation) state.loadMoreFailed = true
  } finally {
    if (current === generation) state.isLoadingMore = false
  }
}

watch(() => [range.value.from, range.value.to, rangeErrorKey.value], loadFirst, { immediate: true })

// Infinite scroll: reaching the end of the list asks for the next slice. "Carregar mais" stays as a fallback.
const sentinel = useTemplateRef<HTMLElement>('sentinel')
let observer: IntersectionObserver | null = null
watch(sentinel, (element) => {
  observer?.disconnect()
  if (!element || typeof IntersectionObserver === 'undefined') return
  observer = new IntersectionObserver((entries) => {
    if (entries.some((entry) => entry.isIntersecting) && !state.loadMoreFailed) void loadMore()
  }, { rootMargin: '400px 0px' })
  observer.observe(element)
})
onBeforeUnmount(() => observer?.disconnect())

/** Like a bank statement: the report is built in the background and arrives by e-mail. */
async function exportCsv(): Promise<void> {
  state.isExporting = true
  state.exportFailed = false
  state.exportedTo = ''
  try {
    state.exportedTo = (await requestHistoryReport(range.value)).email
  } catch {
    state.exportFailed = true
  } finally {
    state.isExporting = false
  }
}
</script>

<template>
  <main class="mx-auto w-full max-w-2xl px-4 pt-6 pb-16">
    <header class="flex items-center gap-3">
      <RouterLink
        to="/painel"
        :aria-label="t('history.back')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:-translate-y-0.5"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </RouterLink>
      <div class="min-w-0 flex-1">
        <h1 class="text-2xl leading-tight">{{ t('history.title') }}</h1>
        <p class="text-sm text-muted">{{ t('history.subtitle') }}</p>
      </div>
    </header>

    <section class="mt-6 rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
      <HistoryPeriodFilter
        v-model:period="period"
        v-model:custom-range="customRange"
        :max-date="todayIso"
        :error="rangeError"
      />
      <p v-if="rangeLabel" class="mt-3 pl-2 text-xs font-semibold text-muted">
        {{ t('history.rangeLabel', { range: rangeLabel }) }}
      </p>
    </section>

    <section class="mt-5 grid grid-cols-2 gap-3" :aria-label="t('history.summaryLabel')" :aria-busy="state.isLoading">
      <MetricCard class="col-span-2" tone="accent" :label="t('history.total')" :value="formatBRL(summary.totalInCents)" />
      <MetricCard :label="t('history.count')" :value="String(summary.count)" />
      <MetricCard :label="t('history.averageTicket')" :value="formatBRL(summary.averageTicketInCents)" />
    </section>

    <div class="mt-5 flex items-center justify-between gap-3">
      <h2 class="text-lg">{{ t('history.listTitle') }}</h2>
      <BaseButton variant="secondary" :disabled="summary.count === 0 || state.isLoading || state.isExporting || Boolean(state.exportedTo)" @click="exportCsv">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
        </svg>
        {{ state.isExporting ? t('history.exportSending') : t('history.exportCsv') }}
      </BaseButton>
    </div>
    <p v-if="state.exportedTo" class="mt-2 rounded-card bg-success/10 px-3 py-2 text-xs font-semibold text-success ring-1 ring-success/40" role="status">
      {{ t('history.exportQueued', { email: state.exportedTo }) }}
    </p>
    <p v-else-if="state.exportFailed" class="mt-1 text-xs text-danger" role="alert">{{ t('history.exportFailed') }}</p>
    <p v-else-if="summary.count" class="mt-1 text-xs text-muted">{{ t('history.exportHint', { count: summary.count }) }}</p>

    <div class="mt-4" :aria-busy="state.isLoading">
      <div v-if="state.isLoading" role="status">
        <span class="sr-only">{{ t('history.loading') }}</span>
        <!-- Same height as a day of records, so the page does not jump when they arrive. -->
        <ul class="grid gap-5" aria-hidden="true">
          <li v-for="index in 3" :key="index" class="h-44 animate-pulse rounded-card bg-surface ring-1 ring-line" />
        </ul>
      </div>
      <p v-else-if="state.hasFailed" class="rounded-card bg-danger/10 px-4 py-6 text-center text-sm text-danger ring-1 ring-danger/40" role="alert">
        {{ t('history.loadFailed') }}
      </p>
      <p v-else-if="rangeError || records.length === 0" class="rounded-card bg-surface px-4 py-8 text-center text-sm text-muted ring-1 ring-line">
        {{ rangeError ? t('history.fixRange') : t('history.empty') }}
      </p>
      <template v-else>
        <HistoryList :groups="groups" :current-year="today.getFullYear()" />
        <div class="mt-6 grid justify-items-center gap-2 text-center">
          <p class="text-xs text-muted">{{ t('history.showing', { shown: records.length, total: summary.count }) }}</p>
          <template v-if="nextCursor">
            <span ref="sentinel" aria-hidden="true" />
            <p v-if="state.loadMoreFailed" class="text-xs text-danger" role="alert">{{ t('history.loadMoreFailed') }}</p>
            <BaseButton variant="secondary" :disabled="state.isLoadingMore" @click="loadMore">
              {{ state.isLoadingMore ? t('history.loadingMore') : t('history.loadMore') }}
            </BaseButton>
          </template>
          <p v-else class="text-xs font-semibold text-muted">{{ t('history.end') }}</p>
        </div>
      </template>
    </div>
  </main>
</template>
