<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useConsentStore } from './consent.store'

/**
 * Privacy notice (LGPD): on the first visit and from "Preferências de cookies". Accepting and refusing are equally
 * easy (same buttons, same weight), and nothing optional runs before a choice.
 */
const { t } = useI18n()
const consent = useConsentStore()

const isVisible = computed(() => !consent.hasDecided || consent.isPanelOpen)
// Opened from "Preferências de cookies": straight to the categories, with the current choice.
const isCustomizing = ref(consent.isPanelOpen)
const performance = ref(consent.allowsPerformance)

watch(
  () => consent.isPanelOpen,
  (isOpen) => {
    if (!isOpen) return
    performance.value = consent.allowsPerformance
    isCustomizing.value = true
  },
)

function save(): void {
  consent.save({ performance: performance.value })
  isCustomizing.value = false
}

const buttonClass =
  'h-10 min-w-0 rounded-full bg-surface px-3 text-sm font-bold text-ink ring-1 ring-line shadow-soft transition hover:ring-accent'
</script>

<template>
  <section
    v-if="isVisible"
    class="fixed inset-x-0 bottom-0 z-40 p-3 sm:bottom-4 sm:p-0"
    role="region"
    :aria-label="t('privacy.banner.label')"
  >
    <div class="mx-auto w-full max-w-xl rounded-[24px] bg-page p-5 shadow-float ring-1 ring-line">
      <h2 class="text-base">{{ t('privacy.banner.title') }}</h2>
      <p class="mt-2 text-sm leading-relaxed text-muted">
        {{ t('privacy.banner.text') }}
        <RouterLink to="/privacidade" class="font-semibold text-ink underline">{{ t('privacy.banner.policy') }}</RouterLink>
      </p>

      <div v-if="isCustomizing" class="mt-4 grid grid-cols-1 gap-3">
        <div class="flex items-start justify-between gap-3 rounded-card bg-raised p-3 ring-1 ring-line">
          <div class="min-w-0">
            <p class="text-sm font-semibold">{{ t('privacy.banner.necessary') }}</p>
            <p class="mt-0.5 text-xs text-muted">{{ t('privacy.banner.necessaryHint') }}</p>
          </div>
          <span class="shrink-0 text-xs font-bold text-muted">{{ t('privacy.banner.alwaysOn') }}</span>
        </div>
        <div class="rounded-card bg-raised p-3 ring-1 ring-line">
          <ToggleSwitch v-model="performance" :label="t('privacy.banner.performance')" :description="t('privacy.banner.performanceHint')" />
        </div>
        <div class="grid grid-cols-2 gap-2">
          <button type="button" :class="buttonClass" @click="save">{{ t('privacy.banner.save') }}</button>
          <button type="button" :class="buttonClass" @click="consent.acceptAll()">{{ t('privacy.banner.acceptAll') }}</button>
        </div>
      </div>

      <div v-else class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
        <button type="button" :class="buttonClass" @click="consent.acceptAll()">{{ t('privacy.banner.acceptAll') }}</button>
        <button type="button" :class="buttonClass" @click="consent.rejectOptional()">{{ t('privacy.banner.rejectOptional') }}</button>
        <button type="button" :class="buttonClass" @click="isCustomizing = true">{{ t('privacy.banner.customize') }}</button>
      </div>
    </div>
  </section>
</template>
