<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { formatBRL } from '@/utils/currency'

/** One expense line: what, when, how much, and a remove button. */
defineProps<{ title: string; detail: string; amountInCents: number; isCounted: boolean }>()
const emit = defineEmits<{ remove: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="flex items-center gap-3 py-3">
    <div class="min-w-0 flex-1">
      <p class="truncate font-bold">{{ title }}</p>
      <p class="truncate text-xs text-muted">{{ detail }}</p>
    </div>
    <p class="shrink-0 font-display font-extrabold tabular-nums" :class="isCounted ? '' : 'text-muted'">
      {{ formatBRL(amountInCents) }}
    </p>
    <button
      type="button"
      :aria-label="t('expenses.remove', { name: title })"
      class="flex size-10 shrink-0 items-center justify-center rounded-full text-muted transition hover:bg-danger/10 hover:text-danger"
      @click="emit('remove')"
    >
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
      </svg>
    </button>
  </div>
</template>
