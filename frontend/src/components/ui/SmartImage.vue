<script setup lang="ts">
import { computed, ref, watch } from 'vue'

/** Image that falls back to a styled placeholder when there is no source or it cannot load. */
const props = withDefaults(defineProps<{ src: string; alt: string; lazy?: boolean }>(), { lazy: false })

const hasFailed = ref(false)
watch(
  () => props.src,
  () => (hasFailed.value = false),
)
const showPlaceholder = computed(() => hasFailed.value || !props.src)
</script>

<template>
  <div
    v-if="showPlaceholder"
    :role="alt ? 'img' : undefined"
    :aria-label="alt || undefined"
    :aria-hidden="alt ? undefined : 'true'"
    class="flex items-center justify-center bg-[radial-gradient(circle_at_30%_20%,var(--raised),var(--surface))] text-muted"
  >
    <svg class="size-8 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
      <circle cx="6" cy="6" r="3" />
      <circle cx="6" cy="18" r="3" />
      <path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12" />
    </svg>
  </div>
  <img v-else :src="src" :alt="alt" :loading="lazy ? 'lazy' : 'eager'" decoding="async" @error="hasFailed = true" />
</template>
