<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useTeamStore } from '@/Domain/Team/team.store'
import { formatBRL, formatMoneyInput, parseMoneyInput } from '@/utils/currency'
import { toIsoDate } from '@/utils/date'
import { useCommissionStore } from './commission.store'
import { formatPercent, parsePercentInput, tenthsToInput } from './commissionFormat'
import {
  SERVICE_COST_SYSTEM_KEY,
  type AdjustmentAppliesTo,
  type AdjustmentKind,
  type AdjustmentStage,
  type CommissionAdjustment,
  type CommissionAdjustmentInput,
} from './commission.model'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const commission = useCommissionStore()
const team = useTeamStore()

const editingId = ref<number | null>(null)
const name = ref('')
const stage = ref<AdjustmentStage>('after')
const kind = ref<AdjustmentKind>('percent')
const appliesTo = ref<AdjustmentAppliesTo>('price')
const valueText = ref('')
const staffId = ref('')
const startsOn = ref(toIsoDate(new Date()))
const error = ref('')
const saving = ref(false)

const stageOptions = computed(() => [
  { value: 'before', label: t('commission.adjustments.stageBefore') },
  { value: 'after', label: t('commission.adjustments.stageAfter') },
])
const kindOptions = computed(() => [
  { value: 'percent', label: t('commission.adjustments.kindPercent') },
  { value: 'fixed', label: t('commission.adjustments.kindFixed') },
])
const appliesToOptions = computed(() => [
  { value: 'price', label: t('commission.adjustments.appliesToPrice') },
  { value: 'commission', label: t('commission.adjustments.appliesToCommission') },
  { value: 'appointment', label: t('commission.adjustments.appliesToAppointment') },
])
const staffOptions = computed(() => [
  { value: '', label: t('commission.rules.everyone') },
  ...team.members.map((member) => ({ value: String(member.id), label: member.name })),
])

function isSystem(adjustment: CommissionAdjustment): boolean {
  return adjustment.systemKey === SERVICE_COST_SYSTEM_KEY
}

function label(adjustment: CommissionAdjustment): string {
  return isSystem(adjustment) ? t('commission.adjustments.serviceCost') : adjustment.name
}

function valueLabel(adjustment: CommissionAdjustment): string {
  if (isSystem(adjustment)) return t('commission.adjustments.systemHint')
  return adjustment.kind === 'percent' ? formatPercent(adjustment.value) : formatBRL(adjustment.value)
}

function toInput(adjustment: CommissionAdjustment, patch: Partial<CommissionAdjustmentInput> = {}): CommissionAdjustmentInput {
  return {
    staffId: adjustment.staffId,
    name: adjustment.name,
    stage: adjustment.stage,
    kind: adjustment.kind,
    appliesTo: adjustment.appliesTo,
    value: adjustment.value,
    active: adjustment.active,
    startsOn: adjustment.startsOn,
    ...patch,
  }
}

function resetForm(): void {
  editingId.value = null
  name.value = ''
  stage.value = 'after'
  kind.value = 'percent'
  appliesTo.value = 'price'
  valueText.value = ''
  staffId.value = ''
  startsOn.value = toIsoDate(new Date())
}

function startEdit(adjustment: CommissionAdjustment): void {
  editingId.value = adjustment.id
  name.value = adjustment.name
  stage.value = adjustment.stage
  kind.value = adjustment.kind
  appliesTo.value = adjustment.appliesTo
  valueText.value = adjustment.kind === 'percent' ? tenthsToInput(adjustment.value) : formatMoneyInput(adjustment.value)
  staffId.value = adjustment.staffId === null ? '' : String(adjustment.staffId)
  startsOn.value = adjustment.startsOn
}

async function run(action: () => Promise<void>): Promise<boolean> {
  error.value = ''
  try {
    await action()
    return true
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
    return false
  }
}

async function submit(): Promise<void> {
  const value = kind.value === 'percent' ? parsePercentInput(valueText.value) : parseMoneyInput(valueText.value)
  if (!name.value.trim() || value === null || value < 0) {
    error.value = t('commission.adjustments.invalidValue')
    return
  }
  const editing = commission.adjustments.find((adjustment) => adjustment.id === editingId.value)
  const input: CommissionAdjustmentInput = {
    staffId: staffId.value ? Number(staffId.value) : null,
    name: name.value.trim(),
    stage: stage.value,
    kind: kind.value,
    appliesTo: appliesTo.value,
    value,
    active: editing?.active ?? true,
    startsOn: startsOn.value,
  }
  saving.value = true
  const id = editingId.value
  const ok = await run(() => (id === null ? commission.addAdjustment(input) : commission.saveAdjustment(id, input)))
  saving.value = false
  if (ok) resetForm()
}

function toggle(adjustment: CommissionAdjustment, active: boolean): Promise<boolean> {
  return run(() => commission.saveAdjustment(adjustment.id, toInput(adjustment, { active })))
}

function remove(adjustment: CommissionAdjustment): Promise<boolean> {
  return run(() => commission.removeAdjustment(adjustment.id))
}
</script>

<template>
  <section class="space-y-4">
    <p class="text-sm text-muted">{{ t('commission.adjustments.intro') }}</p>
    <p v-if="error" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

    <form class="space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" @submit.prevent="submit">
      <TextField v-model="name" :label="t('commission.adjustments.name')" :maxlength="60" />
      <SelectField v-model="stage" :label="t('commission.adjustments.stage')" :options="stageOptions" />
      <div class="grid grid-cols-2 gap-3">
        <SelectField v-model="kind" :label="t('commission.adjustments.kind')" :options="kindOptions" />
        <TextField v-model="valueText" :label="t('commission.adjustments.value')" inputmode="decimal" />
      </div>
      <SelectField v-model="appliesTo" :label="t('commission.adjustments.appliesTo')" :options="appliesToOptions" />
      <SelectField v-model="staffId" :label="t('commission.adjustments.staff')" :options="staffOptions" />
      <TextField v-model="startsOn" type="date" :label="t('commission.adjustments.startsOn')" />
      <div class="flex gap-2">
        <BaseButton type="submit" :disabled="saving">
          {{ editingId === null ? t('commission.adjustments.add') : t('commission.adjustments.save') }}
        </BaseButton>
        <BaseButton v-if="editingId !== null" type="button" variant="ghost" @click="resetForm">
          {{ t('commission.adjustments.cancel') }}
        </BaseButton>
      </div>
    </form>

    <ul v-if="commission.adjustments.length" class="space-y-2" :aria-label="t('commission.adjustments.listLabel')">
      <li v-for="adjustment in commission.adjustments" :key="adjustment.id" class="rounded-card bg-surface p-3 ring-1 ring-line">
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate font-semibold">{{ label(adjustment) }}</p>
            <p class="text-xs text-muted">
              {{ valueLabel(adjustment) }} ·
              {{ adjustment.stage === 'before' ? t('commission.adjustments.stageBefore') : t('commission.adjustments.stageAfter') }}
            </p>
          </div>
          <ToggleSwitch
            :model-value="adjustment.active"
            :label="t('commission.adjustments.toggle', { name: label(adjustment) })"
            @update:model-value="toggle(adjustment, $event)"
          />
        </div>
        <div v-if="!isSystem(adjustment)" class="mt-2 flex gap-2">
          <BaseButton type="button" variant="ghost" @click="startEdit(adjustment)">
            {{ t('commission.adjustments.edit', { name: adjustment.name }) }}
          </BaseButton>
          <BaseButton type="button" variant="ghost" @click="remove(adjustment)">
            {{ t('commission.adjustments.remove', { name: adjustment.name }) }}
          </BaseButton>
        </div>
      </li>
    </ul>
    <p v-else class="text-sm text-muted">{{ t('commission.adjustments.empty') }}</p>
  </section>
</template>
