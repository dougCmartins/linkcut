<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useTeamStore } from '@/Domain/Team/team.store'
import { formatBRL, parseMoneyInput } from '@/utils/currency'
import { useCommissionStore } from './commission.store'
import { parsePercentInput } from './commissionFormat'
import type { CommissionSimulation } from './commission.model'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const commission = useCommissionStore()
const team = useTeamStore()

const priceText = ref('100,00')
const percentText = ref('50')
const costText = ref('')
const useSaved = ref(true)
const result = ref<CommissionSimulation | null>(null)
const simulatedPrice = ref(0)
const error = ref('')
const running = ref(false)

async function run(): Promise<void> {
  error.value = ''
  const price = parseMoneyInput(priceText.value)
  const percent = parsePercentInput(percentText.value)
  const cost = costText.value.trim() ? parseMoneyInput(costText.value) : 0
  if (price === null || percent === null || cost === null) {
    error.value = t('commission.simulator.invalid')
    return
  }
  running.value = true
  try {
    result.value = await commission.simulate({
      priceInCents: price,
      percent,
      costInCents: cost,
      staffId: team.members[0]?.id ?? 1,
      useSavedAdjustments: useSaved.value,
    })
    simulatedPrice.value = price
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    running.value = false
  }
}
</script>

<template>
  <section class="space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" :aria-label="t('commission.simulator.title')">
    <h2 class="text-lg">{{ t('commission.simulator.title') }}</h2>
    <p class="text-sm text-muted">{{ t('commission.simulator.intro') }}</p>
    <form class="space-y-3" @submit.prevent="run">
      <div class="grid grid-cols-3 gap-3">
        <TextField v-model="priceText" :label="t('commission.simulator.price')" inputmode="decimal" />
        <TextField v-model="percentText" :label="t('commission.simulator.percent')" inputmode="decimal" />
        <TextField v-model="costText" :label="t('commission.simulator.cost')" inputmode="decimal" />
      </div>
      <ToggleSwitch v-model="useSaved" :label="t('commission.simulator.useSaved')" />
      <BaseButton type="submit" :disabled="running">{{ t('commission.simulator.run') }}</BaseButton>
    </form>
    <p v-if="error" class="text-sm font-semibold text-danger" role="alert">{{ error }}</p>
    <div v-if="result" class="space-y-1" aria-live="polite">
      <p class="font-semibold" data-testid="simulation-result">
        {{ t('commission.simulator.result', { price: formatBRL(simulatedPrice), amount: formatBRL(result.amountInCents) }) }}
      </p>
      <p class="text-xs text-muted">{{ t('commission.simulator.base', { base: formatBRL(result.baseInCents) }) }}</p>
      <p v-for="line in result.adjustments" :key="`${line.stage}-${line.name}`" class="text-xs text-muted">
        {{ t('commission.simulator.line', { name: line.name, amount: formatBRL(line.amountInCents) }) }}
      </p>
    </div>
  </section>
</template>
