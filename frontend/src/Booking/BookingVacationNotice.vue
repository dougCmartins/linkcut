<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'

defineProps<{ message: string; whatsappUrl: string }>()
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="flex flex-col items-center px-2 py-6 text-center" role="status">
    <span class="flex size-20 items-center justify-center rounded-full bg-raised text-accent ring-1 ring-line shadow-float" aria-hidden="true">
      <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <rect x="3" y="5" width="18" height="16" rx="3" />
        <path d="M16 3v4M8 3v4M3 10h18M9.5 14.5l5 5M14.5 14.5l-5 5" />
      </svg>
    </span>
    <h3 class="mt-6 text-2xl">{{ t('booking.vacationTitle') }}</h3>
    <p class="mt-2 text-muted break-words">{{ message }}</p>
    <BaseButton class="mt-8" variant="secondary" elevated block :href="whatsappUrl">{{ t('bioLink.whatsapp') }}</BaseButton>
    <button type="button" class="mt-4 text-sm font-semibold text-muted hover:text-ink" @click="emit('close')">
      {{ t('booking.close') }}
    </button>
  </div>
</template>
