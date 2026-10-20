<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ExpenseBreakdown } from '@/Domain/Finance/Expenses/expenses.model'
import { HEALTHY_MARGIN_PERCENT, formatMarginPercent } from '@/Domain/Finance/financialSummary'
import { formatBRL } from '@/utils/currency'

/** Content of the margin tooltip: the formula with the barber's own numbers. */
const props = defineProps<{
  revenueInCents: number
  expenses: ExpenseBreakdown
  profitInCents: number
  marginPercent: number
}>()
const { t } = useI18n()

const rows = computed(() => [
  { label: t('dashboard.marginInfo.fixed'), value: props.expenses.fixedInCents },
  { label: t('dashboard.marginInfo.oneOff'), value: props.expenses.oneOffInCents },
  { label: t('dashboard.marginInfo.serviceCosts'), value: props.expenses.serviceCostsInCents },
])
const hasRevenue = computed(() => props.revenueInCents > 0)
const calculation = computed(() =>
  t('dashboard.marginInfo.calculation', {
    profit: formatBRL(props.profitInCents),
    revenue: formatBRL(props.revenueInCents),
    margin: formatMarginPercent(props.marginPercent),
  }),
)
/** Margin as "of every R$ 100 that comes in", rounded like the card: 57.63% -> 57,6% -> R$ 57,60. */
const perHundred = computed(() => formatBRL(Math.round(props.marginPercent * 10) * 10))
</script>

<template>
  <div>
    <p class="font-bold">{{ t('dashboard.marginInfo.title') }}</p>
    <p class="mt-0.5 text-page/75">{{ t('dashboard.marginInfo.formula') }}</p>

    <dl class="mt-3 grid gap-1 tabular-nums">
      <div class="flex justify-between gap-3">
        <dt>{{ t('dashboard.marginInfo.revenue') }}</dt>
        <dd class="font-bold">{{ formatBRL(revenueInCents) }}</dd>
      </div>
      <div v-for="row in rows" :key="row.label" class="flex justify-between gap-3 text-page/75">
        <dt>− {{ row.label }}</dt>
        <dd>{{ formatBRL(row.value) }}</dd>
      </div>
      <div class="mt-1 flex justify-between gap-3 border-t border-page/20 pt-1">
        <dt>= {{ t('dashboard.marginInfo.profit') }}</dt>
        <dd class="font-bold">{{ formatBRL(profitInCents) }}</dd>
      </div>
    </dl>

    <p v-if="hasRevenue" class="mt-3 rounded-xl bg-page/10 px-3 py-2 font-bold tabular-nums">{{ calculation }}</p>
    <p v-else class="mt-3">{{ t('dashboard.marginInfo.noRevenue') }}</p>

    <template v-if="hasRevenue">
      <p v-if="profitInCents >= 0" class="mt-2">{{ t('dashboard.marginInfo.meaning', { value: perHundred }) }}</p>
      <p v-else class="mt-2">{{ t('dashboard.marginInfo.loss') }}</p>
    </template>
    <p class="mt-2 text-page/75">{{ t('dashboard.marginInfo.healthy', { threshold: HEALTHY_MARGIN_PERCENT }) }}</p>
    <p v-if="expenses.totalInCents === 0" class="mt-2 font-semibold text-accent">{{ t('dashboard.marginInfo.noExpenses') }}</p>
    <p class="mt-2 text-xs text-page/65">{{ t('dashboard.marginInfo.notMarkup') }}</p>
  </div>
</template>
