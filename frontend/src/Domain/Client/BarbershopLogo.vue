<script setup lang="ts">
import { computed, ref, watch } from 'vue'

/** The barbershop's photo or logo; without one (or if it does not load), its initials. */
const props = withDefaults(defineProps<{ name: string; src: string | null; size?: 'sm' | 'md' }>(), { size: 'sm' })

const hasFailed = ref(false)
watch(
  () => props.src,
  () => (hasFailed.value = false),
)
const initials = computed(() =>
  props.name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase() ?? '')
    .join(''),
)
</script>

<template>
  <span
    class="flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-raised font-display font-extrabold text-ink ring-1 ring-line"
    :class="size === 'md' ? 'size-14 text-lg' : 'size-10 text-sm'"
    aria-hidden="true"
  >
    <img v-if="src && !hasFailed" :src="src" alt="" class="size-full object-cover" loading="lazy" @error="hasFailed = true" />
    <template v-else>{{ initials }}</template>
  </span>
</template>
