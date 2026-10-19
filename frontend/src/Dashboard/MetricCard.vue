<script setup lang="ts">
type MetricTone = 'default' | 'accent' | 'danger' | 'success'

withDefaults(defineProps<{ label: string; value: string; badge?: string; tone?: MetricTone; hint?: string }>(), {
  badge: undefined,
  tone: 'default',
  hint: undefined,
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
    <div class="mt-2 flex items-end justify-between gap-3">
      <p class="font-display text-[22px] leading-none font-extrabold tracking-tight">{{ value }}</p>
      <span v-if="hint" class="flex shrink-0 items-center gap-1 text-xs font-bold opacity-80">
        {{ hint }}
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
          <path d="m9 6 6 6-6 6" />
        </svg>
      </span>
    </div>
    <span
      v-if="badge"
      class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold"
      :class="BADGE_CLASSES[tone]"
    >
      {{ badge }}
    </span>
  </article>
</template>
