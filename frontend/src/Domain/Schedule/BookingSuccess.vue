<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'

defineProps<{ summary: string }>()
const emit = defineEmits<{ done: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="flex flex-col items-center px-2 py-6 text-center" role="status">
    <span class="flex size-20 items-center justify-center rounded-full bg-accent text-on-accent shadow-float" aria-hidden="true">
      <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
        <path d="m5 12.5 4.5 4.5L19 7.5" />
      </svg>
    </span>
    <h3 class="mt-6 text-2xl">{{ t('booking.successTitle') }}</h3>
    <p class="mt-2 text-muted">{{ t('booking.successText') }}</p>
    <p class="mt-5 rounded-full bg-raised px-4 py-2 text-sm font-semibold ring-1 ring-line">{{ summary }}</p>
    <BaseButton class="mt-8" variant="secondary" elevated block @click="emit('done')">
      {{ t('booking.successDone') }}
    </BaseButton>
  </div>
</template>
