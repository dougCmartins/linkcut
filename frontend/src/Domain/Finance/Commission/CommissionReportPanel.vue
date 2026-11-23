<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useTeamStore } from '@/Domain/Team/team.store'
import { formatBRL } from '@/utils/currency'
import { requestReportCsv } from './commission.api'
import { useCommissionStore } from './commission.store'
import { formatPercent, monthBounds } from './commissionFormat'

/** Owner: any barber, plus close/reopen. Barber: the API already narrows the report to his own entries. */
const props = defineProps<{ isOwner: boolean }>()

const { t } = useI18n()
const { messageKey } = useApiErrors()
const commission = useCommissionStore()
const team = useTeamStore()

const initial = monthBounds(new Date())
const from = ref(initial.from)
const to = ref(initial.to)
const staffId = ref('')
const error = ref('')
const exportedTo = ref('')
const loading = ref(false)

const staffOptions = computed(() => [
  { value: '', label: t('commission.allStaff') },
  ...team.members.map((member) => ({ value: String(member.id), label: member.name })),
])
const selectedStaff = computed(() => (props.isOwner && staffId.value ? Number(staffId.value) : null))
const rangeValid = computed(() => Boolean(from.value) && Boolean(to.value) && from.value <= to.value)
const staffNames = computed(() => new Map(team.members.map((member) => [member.id, member.name])))

async function run(action: () => Promise<void>): Promise<void> {
  error.value = ''
  if (!rangeValid.value) {
    error.value = t('commission.report.invalidRange')
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
  return run(() => commission.loadReport({ from: from.value, to: to.value }, selectedStaff.value))
}

function exportCsv(): Promise<void> {
  return run(async () => {
    const { email } = await requestReportCsv({ from: from.value, to: to.value }, selectedStaff.value)
    exportedTo.value = email
  })
}

async function closePeriod(): Promise<void> {
  await run(async () => {
    await commission.closePeriod({ from: from.value, to: to.value })
    await commission.loadReport({ from: from.value, to: to.value }, selectedStaff.value)
  })
}

async function reopen(id: number): Promise<void> {
  await run(async () => {
    await commission.reopen(id)
    if (commission.report) await commission.loadReport({ from: from.value, to: to.value }, selectedStaff.value)
  })
}

onMounted(() => void load())
</script>

<template>
  <section class="space-y-4">
    <p v-if="error" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

    <form class="space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" @submit.prevent="load">
      <div class="grid grid-cols-2 gap-3">
        <TextField v-model="from" type="date" :label="t('commission.report.from')" />
        <TextField v-model="to" type="date" :label="t('commission.report.to')" />
      </div>
      <SelectField v-if="isOwner" v-model="staffId" :label="t('commission.staffLabel')" :options="staffOptions" />
      <div class="flex flex-wrap gap-2">
        <BaseButton type="submit" :disabled="loading">{{ t('commission.report.load') }}</BaseButton>
        <BaseButton type="button" variant="secondary" :disabled="loading" @click="exportCsv">
          {{ t('commission.report.exportCsv') }}
        </BaseButton>
      </div>
      <p v-if="exportedTo" class="text-sm text-muted" role="status">{{ t('commission.report.exported', { email: exportedTo }) }}</p>
    </form>

    <div v-if="commission.report" class="space-y-3">
      <p class="text-lg font-semibold" data-testid="report-total">
        {{ t('commission.report.total') }}: {{ formatBRL(commission.report.totalInCents) }}
      </p>
      <ul v-if="commission.report.entries.length" class="space-y-2" :aria-label="t('commission.report.listLabel')">
        <li v-for="entry in commission.report.entries" :key="entry.id" class="rounded-card bg-surface p-3 ring-1 ring-line">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-semibold">{{ t('commission.report.entry', { id: entry.bookingRequestId, date: entry.date }) }}</p>
              <p v-if="isOwner" class="text-xs text-muted">{{ staffNames.get(entry.staffId) ?? '—' }}</p>
              <p class="text-xs text-muted">
                {{ t('commission.report.base', { base: formatBRL(entry.baseInCents) }) }} ·
                {{ t('commission.report.percent', { percent: formatPercent(entry.percent) }) }}
              </p>
              <p v-for="line in entry.adjustments" :key="`${line.stage}-${line.name}`" class="text-xs text-muted">
                {{ t('commission.report.adjustment', { name: line.name, amount: formatBRL(line.amountInCents) }) }}
              </p>
            </div>
            <div class="shrink-0 text-right">
              <p class="font-semibold">{{ formatBRL(entry.amountInCents) }}</p>
              <p v-if="entry.closingId !== null" class="text-xs text-muted">{{ t('commission.report.closed') }}</p>
            </div>
          </div>
        </li>
      </ul>
      <p v-else class="text-sm text-muted">{{ t('commission.report.empty') }}</p>
    </div>

    <section v-if="isOwner" class="space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" :aria-label="t('commission.closings.title')">
      <h2 class="text-lg">{{ t('commission.closings.title') }}</h2>
      <p class="text-sm text-muted">{{ t('commission.closings.intro') }}</p>
      <BaseButton type="button" :disabled="loading" @click="closePeriod">{{ t('commission.closings.close') }}</BaseButton>
      <ul v-if="commission.closings.length" class="space-y-2" :aria-label="t('commission.closings.listLabel')">
        <li v-for="closing in commission.closings" :key="closing.id" class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold">{{ t('commission.closings.period', { from: closing.from, to: closing.to }) }}</p>
            <p class="text-xs text-muted">
              {{ closing.reopenedAt ? t('commission.closings.reopened') : t('commission.closings.closedStatus') }}
            </p>
          </div>
          <BaseButton
            v-if="!closing.reopenedAt"
            type="button"
            variant="secondary"
            :disabled="loading"
            :aria-label="t('commission.closings.reopenLabel', { from: closing.from, to: closing.to })"
            @click="reopen(closing.id)"
          >
            {{ t('commission.closings.reopen') }}
          </BaseButton>
        </li>
      </ul>
      <p v-else class="text-sm text-muted">{{ t('commission.closings.empty') }}</p>
    </section>
  </section>
</template>
