<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'

defineProps<{ canSave: boolean; isSaving?: boolean }>()
const emit = defineEmits<{ save: []; discard: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
    <div
      class="mx-auto flex max-w-3xl items-center gap-3 rounded-button [--control-height:3.75rem] bg-surface/95 py-2 pr-2 pl-5 ring-1 ring-line shadow-float backdrop-blur-md"
    >
      <p class="line-clamp-2 min-w-0 flex-1 text-xs leading-tight font-semibold sm:text-sm">
        {{ canSave ? t('settings.saveBar.unsaved') : t('settings.saveBar.fixErrors') }}
      </p>
      <BaseButton variant="ghost" @click="emit('discard')">{{ t('settings.saveBar.discard') }}</BaseButton>
      <BaseButton variant="primary" elevated :disabled="!canSave || isSaving" @click="emit('save')">
        {{ t('settings.saveBar.save') }}
      </BaseButton>
    </div>
  </div>
</template>
