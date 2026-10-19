<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'

const emit = defineEmits<{ book: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
    <div
      class="mx-auto flex max-w-md items-center gap-3 rounded-button [--control-height:3.75rem] bg-surface/90 py-2 pr-2 pl-5 ring-1 ring-line shadow-float backdrop-blur-md"
    >
      <div class="min-w-0 flex-1">
        <p class="truncate font-display text-sm font-extrabold tracking-tight">{{ t('portfolio.stickyTitle') }}</p>
        <p class="truncate text-xs text-muted">{{ t('portfolio.stickySubtitle') }}</p>
      </div>
      <BaseButton variant="primary" elevated @click="emit('book')">{{ t('portfolio.stickyCta') }}</BaseButton>
    </div>
  </div>
</template>
