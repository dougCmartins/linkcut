<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import TextField from '@/components/ui/TextField.vue'
import type { Service } from '@/Booking/booking.model'
import { SERVICE_LIMITS, TEXT_LIMITS } from './settings.validation'

const props = defineProps<{
  service: Service
  canRemove: boolean
  errors: { name?: string; durationMinutes?: string; priceInCents?: string }
}>()
const emit = defineEmits<{ update: [patch: Partial<Service>]; remove: [] }>()
const { t } = useI18n()

/** "50,00" or "50.5" -> 5050 cents. Invalid input -> 0 (shown as an error). */
function parsePrice(text: string): number {
  const value = Number(text.replace(/\s/g, '').replace(',', '.'))
  return Number.isFinite(value) ? Math.round(value * 100) : 0
}

function formatPrice(cents: number): string {
  return cents > 0 ? (cents / 100).toFixed(2).replace('.', ',') : ''
}

// Local text so typing "50," is not reformatted mid-edit.
const priceText = ref(formatPrice(props.service.priceInCents))
watch(
  () => props.service.priceInCents,
  (cents) => {
    if (parsePrice(priceText.value) !== cents) priceText.value = formatPrice(cents)
  },
)

function onPriceInput(text: string): void {
  priceText.value = text
  emit('update', { priceInCents: parsePrice(text) })
}
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
        :model-value="priceText"
        :label="t('settings.schedule.servicePrice')"
        placeholder="0,00"
        :error="errors.priceInCents"
        @update:model-value="onPriceInput"
      />
    </div>
  </div>
</template>
