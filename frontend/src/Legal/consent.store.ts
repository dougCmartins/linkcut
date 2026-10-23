import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { readConsent, writeConsent, type ConsentChoice } from './consent'

/** Single source of the privacy choice: the banner and "Preferências de cookies" write here; Sentry follows (main.ts). */
export const useConsentStore = defineStore('consent', () => {
  const choice = ref<ConsentChoice | null>(readConsent())
  /** "Preferências de cookies" reopens the panel after a choice was made. */
  const isPanelOpen = ref(false)

  const hasDecided = computed(() => choice.value !== null)
  const allowsPerformance = computed(() => choice.value?.performance ?? false)

  function decide(performance: boolean): void {
    choice.value = writeConsent(performance)
    isPanelOpen.value = false
  }

  return {
    choice,
    isPanelOpen,
    hasDecided,
    allowsPerformance,
    acceptAll: () => decide(true),
    rejectOptional: () => decide(false),
    save: (preferences: { performance: boolean }) => decide(preferences.performance),
    openPreferences: () => (isPanelOpen.value = true),
    closePreferences: () => (isPanelOpen.value = false),
  }
})
