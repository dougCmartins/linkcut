<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import UndoToast from '@/components/ui/UndoToast.vue'
import ExpenseForm from './ExpenseForm.vue'
import ExpenseItem from './ExpenseItem.vue'
import { useExpensesStore, type Expense } from './expenses.store'
import type { FixedExpense, FixedExpenseInput, OneOffExpenseInput } from './expenses.model'
import { dueDayInMonth, isDue } from './expenseCalculation'
import { useUndoWindow } from '@/composables/useUndoWindow'
import { formatBRL } from '@/utils/currency'
import { formatDayMonth, formatMonthName, fromIsoDate, toIsoDate } from '@/utils/date'

const { t } = useI18n()
const store = useExpensesStore()
const today = new Date()
const todayIso = toIsoDate(today)
const monthName = formatMonthName(today)

const openForm = ref<'fixed' | 'oneOff' | null>(null)
const undo = useUndoWindow<Expense>()

const oneOffOfMonth = computed(() => store.oneOffOfMonth(today))
const breakdownRows = computed(() => {
  const breakdown = store.breakdown
  if (!breakdown) return []
  return [
    { label: t('expenses.summary.fixed'), value: breakdown.fixedInCents },
    { label: t('expenses.summary.oneOff'), value: breakdown.oneOffInCents },
    { label: t('expenses.summary.serviceCosts'), value: breakdown.serviceCostsInCents },
  ]
})

/** "Dia 5 · já entrou" or "Entra no dia 20". */
function fixedDetail(expense: FixedExpense): string {
  const day = dueDayInMonth(expense, today)
  return isDue(expense, today) ? t('expenses.fixedCounted', { day }) : t('expenses.fixedPending', { day })
}

async function saveFixed(input: FixedExpenseInput): Promise<void> {
  await store.addFixed(input, today)
  openForm.value = null
}

async function saveOneOff(input: OneOffExpenseInput): Promise<void> {
  await store.addOneOff(input, today)
  openForm.value = null
}

async function remove(id: string): Promise<void> {
  const removed = await store.remove(id, today)
  if (removed) undo.offer(removed)
}

async function undoRemove(): Promise<void> {
  const expense = undo.take()
  if (expense) await store.restore(expense, today)
}

const removedName = computed(() => {
  const expense = undo.pending.value
  if (!expense) return ''
  return 'dueDay' in expense ? expense.name : expense.description
})

onMounted(() => store.load(today))
</script>

<template>
  <main class="mx-auto w-full max-w-2xl px-4 pt-6 pb-28">
    <header class="flex items-center gap-3">
      <RouterLink
        to="/painel"
        :aria-label="t('expenses.back')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:-translate-y-0.5"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </RouterLink>
      <div class="min-w-0 flex-1">
        <h1 class="text-2xl leading-tight">{{ t('expenses.title') }}</h1>
        <p class="text-sm text-muted">{{ t('expenses.subtitle') }}</p>
      </div>
    </header>

    <p v-if="store.hasFailed" class="mt-6 rounded-card bg-danger/10 px-4 py-6 text-center text-sm text-danger ring-1 ring-danger/40" role="alert">
      {{ t('expenses.loadFailed') }}
    </p>

    <section class="mt-6 rounded-card bg-surface p-4 ring-1 ring-line shadow-soft" aria-labelledby="expenses-total" :aria-busy="store.isLoading">
      <p id="expenses-total" class="text-xs font-semibold tracking-wide text-muted uppercase">{{ t('expenses.summary.title') }}</p>
      <p class="mt-2 font-display text-[28px] leading-none font-extrabold tracking-tight tabular-nums">
        {{ store.breakdown ? formatBRL(store.breakdown.totalInCents) : '—' }}
      </p>
      <dl class="mt-4 grid gap-2 border-t border-line pt-3 text-sm">
        <div v-for="row in breakdownRows" :key="row.label" class="flex items-center justify-between gap-3">
          <dt class="text-muted">{{ row.label }}</dt>
          <dd class="font-bold tabular-nums">{{ formatBRL(row.value) }}</dd>
        </div>
      </dl>
      <p class="mt-3 text-xs text-muted">
        {{ t('expenses.summary.serviceCostsHint') }}
        <RouterLink to="/painel/configuracoes?aba=agenda" class="font-bold text-accent">{{ t('expenses.summary.serviceCostsLink') }}</RouterLink>
      </p>
    </section>

    <section class="mt-8" aria-labelledby="fixed-title">
      <h2 id="fixed-title" class="text-xl">{{ t('expenses.fixedTitle') }}</h2>
      <p class="mt-1 text-sm text-muted">{{ t('expenses.fixedDescription') }}</p>
      <div class="mt-4 rounded-card bg-surface px-4 ring-1 ring-line shadow-soft">
        <ul v-if="store.fixedByDueDay.length" class="divide-y divide-line">
          <li v-for="expense in store.fixedByDueDay" :key="expense.id">
            <ExpenseItem
              :title="expense.name"
              :detail="fixedDetail(expense)"
              :amount-in-cents="expense.amountInCents"
              :is-counted="isDue(expense, today)"
              @remove="remove(expense.id)"
            />
          </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-muted">{{ t('expenses.fixedEmpty') }}</p>
      </div>
      <ExpenseForm
        v-if="openForm === 'fixed'"
        class="mt-3"
        kind="fixed"
        :today="todayIso"
        @submit-fixed="saveFixed"
        @cancel="openForm = null"
      />
      <BaseButton v-else class="mt-3" variant="secondary" block @click="openForm = 'fixed'">
        + {{ t('expenses.addFixed') }}
      </BaseButton>
    </section>

    <section class="mt-8" aria-labelledby="oneoff-title">
      <h2 id="oneoff-title" class="text-xl">{{ t('expenses.oneOffTitle', { month: monthName }) }}</h2>
      <p class="mt-1 text-sm text-muted">{{ t('expenses.oneOffDescription') }}</p>
      <div class="mt-4 rounded-card bg-surface px-4 ring-1 ring-line shadow-soft">
        <ul v-if="oneOffOfMonth.length" class="divide-y divide-line">
          <li v-for="expense in oneOffOfMonth" :key="expense.id">
            <ExpenseItem
              :title="expense.description"
              :detail="formatDayMonth(fromIsoDate(expense.date))"
              :amount-in-cents="expense.amountInCents"
              :is-counted="true"
              @remove="remove(expense.id)"
            />
          </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-muted">{{ t('expenses.oneOffEmpty') }}</p>
      </div>
      <ExpenseForm
        v-if="openForm === 'oneOff'"
        class="mt-3"
        kind="oneOff"
        :today="todayIso"
        @submit-one-off="saveOneOff"
        @cancel="openForm = null"
      />
      <BaseButton v-else class="mt-3" variant="secondary" block @click="openForm = 'oneOff'">
        + {{ t('expenses.addOneOff') }}
      </BaseButton>
    </section>

    <UndoToast
      v-if="undo.pending.value"
      :message="t('expenses.removedToast', { name: removedName })"
      :action-label="t('expenses.undo')"
      @action="undoRemove"
    />
  </main>
</template>
