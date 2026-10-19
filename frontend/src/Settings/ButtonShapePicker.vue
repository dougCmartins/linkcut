<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { BUTTON_SHAPES, type ButtonShape } from '@/types/theme'

defineProps<{ label: string }>()
const selected = defineModel<ButtonShape>({ required: true })
const { t } = useI18n()
</script>

<template>
  <div role="radiogroup" :aria-label="label" class="grid grid-cols-3 gap-3">
    <button
      v-for="shape in BUTTON_SHAPES"
      :key="shape"
      type="button"
      role="radio"
      :aria-checked="shape === selected"
      class="min-w-0 rounded-card bg-raised p-2.5 text-center ring-1 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :class="shape === selected ? 'ring-2 ring-accent shadow-float' : 'ring-line shadow-soft'"
      @click="selected = shape"
    >
      <!-- 36px tall, same as the Figma kit, with that shape's radius -->
      <span
        :data-button-shape="shape"
        class="block h-9 w-full rounded-button [--control-height:2.25rem]"
        :class="shape === selected ? 'bg-accent shadow-[0_3px_0_0_var(--accent-edge)]' : 'bg-ink/80'"
        aria-hidden="true"
      />
      <span class="mt-2.5 block truncate font-display text-xs font-extrabold tracking-tight sm:text-sm">
        {{ t(`settings.appearance.buttonShapes.${shape}`) }}
      </span>
    </button>
  </div>
</template>
