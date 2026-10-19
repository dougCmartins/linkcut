<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { THEME_PALETTES, type ThemeMode, type ThemePalette } from '@/types/theme'

defineProps<{ mode: ThemeMode; label: string }>()
const selected = defineModel<ThemePalette>({ required: true })
const { t } = useI18n()
</script>

<template>
  <div role="radiogroup" :aria-label="label" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
    <button
      v-for="palette in THEME_PALETTES"
      :key="palette"
      type="button"
      role="radio"
      :aria-checked="palette === selected"
      class="rounded-card p-1.5 text-left ring-1 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :class="palette === selected ? 'ring-2 ring-accent shadow-float' : 'ring-line shadow-soft'"
      @click="selected = palette"
    >
      <!-- Each swatch renders with its own palette tokens from core.css -->
      <span :data-theme="palette" :data-mode="mode" class="block rounded-xl bg-page p-3 text-ink" aria-hidden="true">
        <span class="block rounded-lg bg-surface p-2 ring-1 ring-line">
          <span class="block h-1.5 w-10 rounded-full bg-ink/80" />
          <span class="mt-1.5 block h-1.5 w-14 rounded-full bg-muted/60" />
        </span>
        <span class="mt-2 flex items-center gap-1.5">
          <span class="h-5 flex-1 rounded-full bg-accent shadow-[0_2px_0_0_var(--accent-edge)]" />
          <span class="size-5 rounded-full bg-raised ring-1 ring-line" />
        </span>
      </span>
      <span class="mt-2 flex items-center justify-between px-1.5 pb-1">
        <span class="font-display text-sm font-extrabold tracking-tight">{{ t(`settings.appearance.palettes.${palette}`) }}</span>
        <svg v-if="palette === selected" class="size-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
          <path d="m5 12.5 4.5 4.5L19 7.5" />
        </svg>
      </span>
    </button>
  </div>
</template>
