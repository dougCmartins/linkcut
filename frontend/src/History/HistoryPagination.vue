<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ChoiceChips from '@/components/ui/ChoiceChips.vue'
import { PAGE_SIZE_OPTIONS, type PageSize } from './history.model'

const props = defineProps<{ page: number; totalPages: number; firstItem: number; lastItem: number; total: number }>()
const pageSize = defineModel<PageSize>('pageSize', { required: true })
const emit = defineEmits<{ changePage: [page: number] }>()
const { t } = useI18n()

const sizeOptions = PAGE_SIZE_OPTIONS.map((size) => ({ value: size, label: String(size) }))
const rangeLabel = computed(() =>
  t('history.showing', { first: props.firstItem, last: props.lastItem, total: props.total }),
)

const navButton =
  'flex h-10 items-center gap-1 rounded-full bg-surface px-4 text-sm font-bold ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 disabled:translate-y-0 disabled:opacity-40'
</script>

<template>
  <nav class="grid gap-4" :aria-label="t('history.pagination')">
    <ChoiceChips v-model="pageSize" :options="sizeOptions" :label="t('history.pageSize')" />
    <div class="flex items-center justify-between gap-3">
      <button type="button" :class="navButton" :disabled="page <= 1" @click="emit('changePage', page - 1)">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
        {{ t('history.previous') }}
      </button>
      <p class="text-center text-xs font-semibold text-muted" aria-live="polite">
        <span class="block">{{ rangeLabel }}</span>
        <span class="block">{{ t('history.pageOf', { page, total: totalPages }) }}</span>
      </p>
      <button type="button" :class="navButton" :disabled="page >= totalPages" @click="emit('changePage', page + 1)">
        {{ t('history.next') }}
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
          <path d="m9 6 6 6-6 6" />
        </svg>
      </button>
    </div>
  </nav>
</template>
