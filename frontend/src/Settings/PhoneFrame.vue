<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { themeAttributes } from '@/composables/useTheme'
import type { ThemeSettings } from './settings.model'

/** Mini phone that renders its content with the given theme (independent from the page theme). */
const props = defineProps<{ theme: ThemeSettings }>()
const { t } = useI18n()
const attributes = computed(() => themeAttributes(props.theme))
</script>

<template>
  <figure class="mx-auto w-full max-w-[280px]">
    <figcaption class="mb-3 text-center text-xs font-bold tracking-[0.18em] text-muted uppercase">
      {{ t('settings.preview') }}
    </figcaption>
    <div class="rounded-[2.5rem] bg-[#0b0b0d] p-2.5 shadow-float ring-1 ring-line">
      <div
        v-bind="attributes"
        data-testid="phone-preview"
        class="scrollbar-none relative h-[540px] overflow-y-auto rounded-[2rem] bg-page text-ink"
        aria-hidden="true"
        inert
      >
        <slot />
      </div>
    </div>
  </figure>
</template>
