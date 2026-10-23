<script setup lang="ts">
import { useI18n } from 'vue-i18n'

/** The round "share" button of the public pages (top of the page, next to the theme switch). */
defineEmits<{ click: [] }>()
const { t } = useI18n()
</script>

<template>
  <button
    type="button"
    :aria-label="t('share.open')"
    :title="t('share.open')"
    class="flex size-10 items-center justify-center rounded-full bg-surface/80 text-ink ring-1 ring-line shadow-soft backdrop-blur transition hover:-translate-y-0.5"
    @click="$emit('click')"
  >
    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path d="M12 15V3M7 8l5-5 5 5M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6" />
    </svg>
  </button>
</template>
