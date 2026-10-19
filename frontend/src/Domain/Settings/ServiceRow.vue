<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import TextField from '@/components/ui/TextField.vue'
import type { Service } from '@/Booking/booking.model'
import { SERVICE_LIMITS, TEXT_LIMITS } from './settings.validation'
import { formatBRL, formatMoneyInput, parseMoneyInput } from '@/utils/currency'

const props = defineProps<{
  service: Service
  canRemove: boolean
  errors: { name?: string; durationMinutes?: string; priceInCents?: string; costInCents?: string }
}>()
const emit = defineEmits<{ update: [patch: Partial<Service>]; remove: [] }>()
const { t } = useI18n()

/**
 * Money field with local text, so typing "50," is not reformatted mid-edit.
 * Invalid input becomes `fallback` cents (shown as an error by the validator).
 */
function useMoneyField(read: () => number | undefined, field: 'priceInCents' | 'costInCents', fallback: number) {
  const text = ref(formatMoneyInput(read() ?? 0))
  watch(read, (cents) => {
    if ((parseMoneyInput(text.value) ?? 0) !== (cents ?? 0)) text.value = formatMoneyInput(cents ?? 0)
  })
  function onInput(value: string): void {
    text.value = value
    emit('update', { [field]: value.trim() ? (parseMoneyInput(value) ?? fallback) : 0 })
  }
  return { text, onInput }
}

const price = useMoneyField(() => props.service.priceInCents, 'priceInCents', 0)
// -1 makes an unreadable cost fail validation instead of silently becoming zero.
const cost = useMoneyField(() => props.service.costInCents, 'costInCents', -1)

/** "Sobram R$ 44,00 (88%) por atendimento": what one appointment leaves after its cost. */
const leftPerAppointment = computed(() => {
  const { priceInCents, costInCents = 0 } = props.service
  if (priceInCents <= 0 || costInCents <= 0 || costInCents >= priceInCents) return undefined
  const left = priceInCents - costInCents
  const percent = `${Math.round((left / priceInCents) * 100)}%`
  return t('settings.schedule.serviceCostLeft', { value: formatBRL(left), percent })
})
</script>

<template>
  <div class="rounded-card bg-raised p-4 ring-1 ring-line">
    <div class="flex items-start gap-3">
      <TextField
        class="min-w-0 flex-1"
        :model-value="service.name"
        :label="t('settings.schedule.serviceName')"
        :maxlength="TEXT_LIMITS.serviceName.max"
        :error="errors.name"
        @update:model-value="emit('update', { name: $event })"
      />
      <button
        v-if="canRemove"
        type="button"
        :aria-label="t('settings.schedule.removeService', { name: service.name || '—' })"
        class="mt-6 flex size-12 shrink-0 items-center justify-center rounded-full text-danger ring-1 ring-danger/40 transition hover:bg-danger/10"
        @click="emit('remove')"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
        </svg>
      </button>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-3">
      <TextField
        type="number"
        inputmode="numeric"
        :min="SERVICE_LIMITS.minDuration"
        :max="SERVICE_LIMITS.maxDuration"
        :step="SERVICE_LIMITS.durationStep"
        :model-value="String(service.durationMinutes)"
        :label="t('settings.schedule.serviceDuration')"
        :error="errors.durationMinutes"
        @update:model-value="emit('update', { durationMinutes: Number($event) })"
      />
      <TextField
        inputmode="decimal"
        :model-value="price.text.value"
        :label="t('settings.schedule.servicePrice')"
        placeholder="0,00"
        :error="errors.priceInCents"
        @update:model-value="price.onInput"
      />
    </div>
    <TextField
      class="mt-3"
      inputmode="decimal"
      :model-value="cost.text.value"
      :label="t('settings.schedule.serviceCost')"
      placeholder="0,00"
      :error="errors.costInCents"
      :hint="leftPerAppointment ?? t('settings.schedule.serviceCostHint')"
      @update:model-value="cost.onInput"
    />
  </div>
</template>
