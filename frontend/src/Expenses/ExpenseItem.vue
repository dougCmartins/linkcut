<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { formatBRL } from '@/utils/currency'

/** One expense: name and value on top; when it counts and the edit/remove buttons below. */
defineProps<{ title: string; detail: string; amountInCents: number; isCounted: boolean }>()
const emit = defineEmits<{ edit: []; remove: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="py-3">
    <div class="flex items-baseline gap-3">
      <p class="min-w-0 flex-1 truncate font-bold">{{ title }}</p>
      <p class="shrink-0 font-display font-extrabold tabular-nums" :class="isCounted ? '' : 'text-muted'">
        {{ formatBRL(amountInCents) }}
      </p>
    </div>
    <div class="flex items-center gap-3">
      <p class="min-w-0 flex-1 truncate text-xs text-muted">{{ detail }}</p>
      <div class="-mr-2 -mb-2 flex shrink-0">
        <button
          type="button"
          :aria-label="t('expenses.edit', { name: title })"
          class="flex size-10 items-center justify-center rounded-full text-muted transition hover:bg-raised hover:text-ink"
          @click="emit('edit')"
        >
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
          </svg>
        </button>
        <button
          type="button"
          :aria-label="t('expenses.remove', { name: title })"
          class="flex size-10 items-center justify-center rounded-full text-muted transition hover:bg-danger/10 hover:text-danger"
          @click="emit('remove')"
        >
          <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
