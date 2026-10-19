<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { buttonSkinClasses } from '@/components/ui/buttonSkin'
import { BUTTON_STYLES, type ButtonShape, type ButtonStyle } from '@/types/theme'

defineProps<{ label: string; shape: ButtonShape }>()
const selected = defineModel<ButtonStyle>({ required: true })
const { t } = useI18n()
</script>

<template>
  <div role="radiogroup" :aria-label="label" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
    <button
      v-for="style in BUTTON_STYLES"
      :key="style"
      type="button"
      role="radio"
      :aria-checked="style === selected"
      class="min-w-0 rounded-card bg-page p-3 pb-2.5 text-center ring-1 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :class="style === selected ? 'ring-2 ring-accent shadow-float' : 'ring-line shadow-soft'"
      @click="selected = style"
    >
      <!-- Real sample: 36px tall like the Figma kit, with the chosen shape -->
      <span :data-button-shape="shape" class="block pr-1.5 pb-1.5" aria-hidden="true">
        <span
          class="pointer-events-none block h-9 w-full rounded-button [--control-height:2.25rem]"
          :class="buttonSkinClasses(style, 'primary')"
        />
      </span>
      <span class="mt-1.5 block truncate font-display text-xs font-extrabold tracking-tight sm:text-sm">
        {{ t(`settings.appearance.buttonStyles.${style}`) }}
      </span>
    </button>
  </div>
</template>
