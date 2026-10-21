<script setup lang="ts">
import { computed, reactive, ref, shallowRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import MetricCard from '@/components/ui/MetricCard.vue'
import HistoryList from './HistoryList.vue'
import HistoryPagination from './HistoryPagination.vue'
import HistoryPeriodFilter from './HistoryPeriodFilter.vue'
import { downloadHistoryCsv, fetchHistoryPage } from './history.api'
import { historyFileName } from './historyExport'
import { HistoryPeriod, type DateRange, type HistoryPage, type PageSize } from './history.model'
import { customRangeError, monthToDate, periodRange } from './historyPeriod'
import { groupByDay, pageBounds } from './historySummary'
import { formatBRL } from '@/utils/currency'
import { downloadBlob } from '@/utils/download'
import { fromIsoDate, toIsoDate } from '@/utils/date'

const { t } = useI18n()
const today = new Date()
const todayIso = toIsoDate(today)

const period = ref<HistoryPeriod>(HistoryPeriod.Month)
const customRange = ref<DateRange>(monthToDate(today))
const pageSize = ref<PageSize>(50)
const page = ref(1)

const EMPTY_SUMMARY = { totalInCents: 0, count: 0, averageTicketInCents: 0 }

/** The API pages and totals the history; only read here, so no deep reactivity. */
const result = shallowRef<HistoryPage | null>(null)
const state = reactive({ isLoading: false, hasFailed: false, isExporting: false, exportFailed: false })

const rangeErrorKey = computed(() => (period.value === HistoryPeriod.Custom ? customRangeError(customRange.value) : null))
const rangeError = computed(() => (rangeErrorKey.value ? t(`history.errors.${rangeErrorKey.value}`) : undefined))
const range = computed(() => periodRange(period.value, today, customRange.value))

const rangeFormatter = new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' })
const rangeLabel = computed(() =>
  rangeErrorKey.value ? '' : rangeFormatter.formatRange(fromIsoDate(range.value.from), fromIsoDate(range.value.to)),
)

const records = computed(() => result.value?.items ?? [])
const summary = computed(() => result.value?.summary ?? EMPTY_SUMMARY)
const bounds = computed(() => pageBounds(result.value?.page ?? 1, pageSize.value, records.value.length))
const groups = computed(() => groupByDay(records.value))

let latestRequest = 0

async function load(): Promise<void> {
  const request = ++latestRequest
  if (rangeErrorKey.value) {
    // Invalid dates: drop any answer still on its way and show nothing.
    result.value = null
    state.isLoading = false
    return
  }
  state.isLoading = true
  state.hasFailed = false
  try {
    const loaded = await fetchHistoryPage(range.value, page.value, pageSize.value)
    if (request === latestRequest) result.value = loaded
  } catch {
    if (request === latestRequest) state.hasFailed = true
  } finally {
    if (request === latestRequest) state.isLoading = false
  }
}

// New filter or page size: back to page 1. Any change reloads from the API.
watch(
  () => [range.value.from, range.value.to, rangeErrorKey.value, pageSize.value],
  () => {
    page.value = 1
    void load()
  },
  { immediate: true },
)

function changePage(next: number): void {
  page.value = next
  void load()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function exportCsv(): Promise<void> {
  state.isExporting = true
  state.exportFailed = false
  try {
    downloadBlob(historyFileName(range.value), await downloadHistoryCsv(range.value))
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
      <BaseButton variant="secondary" :disabled="summary.count === 0 || state.isLoading || state.isExporting" @click="exportCsv">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
        </svg>
        {{ t('history.exportCsv') }}
      </BaseButton>
    </div>
    <p v-if="state.exportFailed" class="mt-1 text-xs text-danger" role="alert">{{ t('history.exportFailed') }}</p>
    <p v-else-if="summary.count" class="mt-1 text-xs text-muted">{{ t('history.exportHint', { count: summary.count }) }}</p>

    <div class="mt-4" :aria-busy="state.isLoading">
      <p v-if="state.isLoading" class="rounded-card bg-surface px-4 py-8 text-center text-sm text-muted ring-1 ring-line" role="status">
        {{ t('history.loading') }}
      </p>
      <p v-else-if="state.hasFailed" class="rounded-card bg-danger/10 px-4 py-6 text-center text-sm text-danger ring-1 ring-danger/40" role="alert">
        {{ t('history.loadFailed') }}
      </p>
      <p v-else-if="rangeError || records.length === 0" class="rounded-card bg-surface px-4 py-8 text-center text-sm text-muted ring-1 ring-line">
        {{ rangeError ? t('history.fixRange') : t('history.empty') }}
      </p>
      <template v-else>
        <HistoryList :groups="groups" :current-year="today.getFullYear()" />
        <HistoryPagination
          v-model:page-size="pageSize"
          class="mt-6"
          :page="result?.page ?? 1"
          :total-pages="result?.lastPage ?? 1"
          :first-item="bounds.firstItem"
          :last-item="bounds.lastItem"
          :total="summary.count"
          @change-page="changePage"
        />
      </template>
    </div>
  </main>
</template>
