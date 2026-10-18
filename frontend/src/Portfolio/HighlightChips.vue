<script setup lang="ts">
import type { Highlight } from '@/Settings/settings.model'

defineProps<{ highlights: Highlight[] }>()
</script>

<template>
  <ul class="flex gap-2">
    <li
      v-for="(item, index) in highlights"
      :key="index"
      class="min-w-0 flex-1 basis-0 rounded-xl bg-raised px-3 py-3 ring-1 ring-line"
    >
      <p class="text-[10px] font-semibold tracking-[0.1em] break-words text-muted uppercase">{{ item.label }}</p>
      <p class="mt-1 font-display text-[13px] leading-tight font-extrabold tracking-tight break-words">{{ item.value }}</p>
    </li>
  </ul>
</template>
