<script setup lang="ts">
type MetricTone = 'default' | 'accent' | 'danger' | 'success'

withDefaults(defineProps<{ label: string; value: string; badge?: string; tone?: MetricTone }>(), {
  badge: undefined,
  tone: 'default',
})

const BADGE_CLASSES: Record<MetricTone, string> = {
  default: 'bg-raised text-muted',
  accent: 'bg-accent text-on-accent',
  danger: 'bg-danger/15 text-danger',
  success: 'bg-success/15 text-success',
}
</script>

<template>
  <article
    class="rounded-card p-4 ring-1 shadow-soft"
    :class="tone === 'accent' ? 'bg-accent text-on-accent ring-accent' : 'bg-surface ring-line'"
  >
    <p class="text-xs font-semibold tracking-wide uppercase" :class="tone === 'accent' ? 'opacity-75' : 'text-muted'">
      {{ label }}
    </p>
    <p class="mt-2 font-display text-[22px] leading-none font-extrabold tracking-tight">{{ value }}</p>
    <span
      v-if="badge"
      class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold"
      :class="BADGE_CLASSES[tone]"
    >
      {{ badge }}
    </span>
  </article>
</template>
