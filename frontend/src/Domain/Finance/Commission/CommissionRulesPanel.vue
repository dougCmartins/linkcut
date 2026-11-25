<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { useTeamStore } from '@/Domain/Team/team.store'
import { toIsoDate } from '@/utils/date'
import { useCommissionStore } from './commission.store'
import { formatPercent, parsePercentInput } from './commissionFormat'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const commission = useCommissionStore()
const settings = useSettingsStore()
const team = useTeamStore()

const serviceId = ref('')
const staffId = ref('')
const percentText = ref('')
const startsOn = ref(toIsoDate(new Date()))
const error = ref('')
const saving = ref(false)

const services = computed(() => settings.schedule.services)
const serviceOptions = computed(() => services.value.map((service) => ({ value: service.id, label: service.name })))
const staffOptions = computed(() => [
  { value: '', label: t('commission.rules.everyone') },
  ...team.members.map((member) => ({ value: String(member.id), label: member.name })),
])

function serviceName(id: number): string {
  return services.value.find((service) => service.id === String(id))?.name ?? t('commission.rules.deletedService')
}

function staffName(id: number | null): string {
  return id === null ? t('commission.rules.everyone') : (team.members.find((member) => member.id === id)?.name ?? '—')
}

async function submit(): Promise<void> {
  error.value = ''
  const percent = parsePercentInput(percentText.value)
  if (!serviceId.value || percent === null) {
    error.value = t('commission.rules.invalidPercent')
    return
  }
  saving.value = true
  try {
    await commission.addRule({
      serviceId: Number(serviceId.value),
      staffId: staffId.value ? Number(staffId.value) : null,
      percent,
      startsOn: startsOn.value,
    })
    percentText.value = ''
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    saving.value = false
  }
}

async function remove(id: number): Promise<void> {
  error.value = ''
  try {
    await commission.removeRule(id)
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}
</script>

<template>
  <section class="space-y-4">
    <p class="text-sm text-muted">{{ t('commission.rules.intro') }}</p>
    <p v-if="error" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

    <form class="space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" @submit.prevent="submit">
      <SelectField v-model="serviceId" :label="t('commission.rules.service')" :options="serviceOptions" :placeholder="t('commission.rules.servicePlaceholder')" />
      <SelectField v-model="staffId" :label="t('commission.rules.staff')" :options="staffOptions" />
      <div class="grid grid-cols-2 gap-3">
        <TextField v-model="percentText" :label="t('commission.rules.percent')" inputmode="decimal" />
        <TextField v-model="startsOn" type="date" :label="t('commission.rules.startsOn')" />
      </div>
      <BaseButton type="submit" :disabled="saving">{{ t('commission.rules.add') }}</BaseButton>
    </form>

    <ul v-if="commission.rules.length" class="space-y-2" :aria-label="t('commission.rules.listLabel')">
      <li
        v-for="rule in commission.rules"
        :key="rule.id"
        class="flex items-center justify-between gap-3 rounded-card bg-surface p-3 ring-1 ring-line"
      >
        <div class="min-w-0">
          <p class="truncate font-semibold">{{ serviceName(rule.serviceId) }} · {{ formatPercent(rule.percent) }}</p>
          <p class="text-xs text-muted">{{ staffName(rule.staffId) }} · {{ rule.startsOn }}</p>
        </div>
        <BaseButton
          type="button"
          variant="ghost"
          :aria-label="t('commission.rules.remove', { service: serviceName(rule.serviceId) })"
          @click="remove(rule.id)"
        >
          ✕
        </BaseButton>
      </li>
    </ul>
    <p v-else class="text-sm text-muted">{{ t('commission.rules.empty') }}</p>
  </section>
</template>
