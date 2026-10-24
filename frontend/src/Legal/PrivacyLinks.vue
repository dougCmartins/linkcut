<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useConsentStore } from './consent.store'

/** "Privacidade · Preferências de cookies · © linkcut": the footer of the public pages. */
const { t } = useI18n()
const consent = useConsentStore()
const year = new Date().getFullYear()
</script>

<template>
  <p class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs font-semibold text-muted">
    <RouterLink to="/privacidade" class="transition hover:text-ink">{{ t('privacy.links.policy') }}</RouterLink>
    <span aria-hidden="true">·</span>
    <RouterLink to="/termos" class="transition hover:text-ink">{{ t('privacy.links.terms') }}</RouterLink>
    <span aria-hidden="true">·</span>
    <button type="button" class="font-semibold transition hover:text-ink" @click="consent.openPreferences()">
      {{ t('privacy.links.preferences') }}
    </button>
    <span aria-hidden="true">·</span>
    <span>{{ t('privacy.links.copyright', { year }) }}</span>
  </p>
</template>
