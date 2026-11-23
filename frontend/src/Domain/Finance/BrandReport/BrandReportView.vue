<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { monthBounds } from '@/Domain/Finance/Commission/commissionFormat'
import { formatBRL } from '@/utils/currency'
import { fetchBrandReport, requestBrandReportCsv } from './brandReport.api'
import type { BrandReport } from './brandReport.model'

const { t } = useI18n()
const { messageKey } = useApiErrors()

const initial = monthBounds(new Date())
const from = ref(initial.from)
const to = ref(initial.to)
const report = ref<BrandReport | null>(null)
const error = ref('')
const exportedTo = ref('')
const loading = ref(false)

const rangeValid = computed(() => Boolean(from.value) && Boolean(to.value) && from.value <= to.value)

async function run(action: () => Promise<void>): Promise<void> {
  error.value = ''
  if (!rangeValid.value) {
    error.value = t('brandReport.invalidRange')
    return
  }
  loading.value = true
  try {
    await action()
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    loading.value = false
  }
}

function load(): Promise<void> {
  exportedTo.value = ''
  return run(async () => {
    report.value = await fetchBrandReport({ from: from.value, to: to.value })
  })
}

function exportCsv(): Promise<void> {
  return run(async () => {
    const { email } = await requestBrandReportCsv({ from: from.value, to: to.value })
    exportedTo.value = email
  })
}

onMounted(() => void load())
</script>

<template>
  <div class="mx-auto max-w-lg px-4 py-6">
    <header class="mb-6">
      <RouterLink to="/painel" class="text-sm font-semibold text-accent">← {{ t('brandReport.back') }}</RouterLink>
      <h1 class="mt-2 text-2xl leading-tight">{{ t('brandReport.title') }}</h1>
      <p class="mt-1 text-sm text-muted">{{ t('brandReport.subtitle') }}</p>
    </header>

    <p v-if="error" class="mb-4 rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

    <form class="mb-4 space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" @submit.prevent="load">
      <div class="grid grid-cols-2 gap-3">
        <TextField v-model="from" type="date" :label="t('brandReport.from')" />
        <TextField v-model="to" type="date" :label="t('brandReport.to')" />
      </div>
      <div class="flex flex-wrap gap-2">
        <BaseButton type="submit" :disabled="loading">{{ t('brandReport.load') }}</BaseButton>
        <BaseButton type="button" variant="secondary" :disabled="loading" @click="exportCsv">
          {{ t('brandReport.exportCsv') }}
        </BaseButton>
      </div>
      <p v-if="exportedTo" class="text-sm text-muted" role="status">{{ t('brandReport.exported', { email: exportedTo }) }}</p>
    </form>

    <div v-if="report" class="space-y-4">
      <section class="rounded-card bg-surface p-4 ring-1 ring-line" :aria-label="t('brandReport.total')">
        <h2 class="text-lg">{{ t('brandReport.total') }}</h2>
        <dl class="mt-2 grid grid-cols-2 gap-3 text-sm">
          <div>
            <dt class="text-xs text-muted">{{ t('brandReport.revenue') }}</dt>
            <dd class="font-semibold" data-testid="brand-total-revenue">{{ formatBRL(report.total.revenueInCents) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-muted">{{ t('brandReport.count') }}</dt>
            <dd class="font-semibold">{{ report.total.count }}</dd>
          </div>
          <div>
            <dt class="text-xs text-muted">{{ t('brandReport.averageTicket') }}</dt>
            <dd class="font-semibold">{{ formatBRL(report.total.averageTicketInCents) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-muted">{{ t('brandReport.commission') }}</dt>
            <dd class="font-semibold">{{ formatBRL(report.total.commissionInCents) }}</dd>
          </div>
        </dl>
      </section>

      <ul class="space-y-2" :aria-label="t('brandReport.unitsLabel')">
        <li v-for="unit in report.units" :key="unit.unitId" class="rounded-card bg-surface p-3 ring-1 ring-line">
          <p class="truncate font-semibold">
            {{ unit.isMain ? t('organization.mainUnit', { name: unit.name }) : unit.name }}
          </p>
          <p class="text-xs text-muted">
            {{ t('brandReport.revenue') }}: {{ formatBRL(unit.totals.revenueInCents) }} ·
            {{ t('brandReport.count') }}: {{ unit.totals.count }} ·
            {{ t('brandReport.averageTicket') }}: {{ formatBRL(unit.totals.averageTicketInCents) }} ·
            {{ t('brandReport.commission') }}: {{ formatBRL(unit.totals.commissionInCents) }}
          </p>
        </li>
      </ul>
    </div>
  </div>
</template>
