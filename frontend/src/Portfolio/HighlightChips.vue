<script setup lang="ts">
import type { Highlight } from '@/mocks/barber'

defineProps<{ highlights: Highlight[] }>()
</script>

<template>
  <ul class="grid grid-cols-3 gap-2">
    <li v-for="item in highlights" :key="item.label" class="min-w-0 rounded-xl bg-raised px-3 py-3 ring-1 ring-line">
      <p class="break-words text-[10px] font-semibold tracking-[0.1em] text-muted uppercase">{{ item.label }}</p>
      <p class="mt-1 font-display text-[13px] leading-tight font-extrabold tracking-tight">{{ item.value }}</p>
    </li>
  </ul>
</template>
