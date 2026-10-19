<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useVisitorMode } from '@/composables/useTheme'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { ThemeMode } from '@/types/theme'

/** Light/dark switch for visitors of the public pages. The barber's mode is the default. */
const { t } = useI18n()
const publicPageStore = usePublicPageStore()
const { visitorMode, toggleVisitorMode } = useVisitorMode()

const currentMode = computed(() => visitorMode.value ?? publicPageStore.theme.mode)
const isDark = computed(() => currentMode.value === ThemeMode.Dark)
</script>

<template>
  <button
    type="button"
    :aria-label="t('palette.toggle')"
    :aria-pressed="isDark"
    class="flex h-10 items-center gap-2 rounded-full bg-surface/80 px-3 text-xs font-semibold text-ink ring-1 ring-line shadow-soft backdrop-blur transition hover:-translate-y-0.5"
    @click="toggleVisitorMode(currentMode)"
  >
    <svg v-if="isDark" class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
    </svg>
    <svg v-else class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <circle cx="12" cy="12" r="4" />
      <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
    </svg>
    {{ isDark ? t('palette.dark') : t('palette.light') }}
  </button>
</template>
