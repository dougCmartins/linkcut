<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { safeClientRedirect } from './client.guard'

/**
 * A normal link to our API, which redirects to Google. No Google script, so the page CSP stays as it is.
 * `returnTo` wins; otherwise the login's `?redirect=` is kept. Only an in-app path is sent.
 */
const props = defineProps<{ returnTo?: string }>()
const { t } = useI18n()
const route = useRoute()

const href = computed(() => {
  const volta = safeClientRedirect(props.returnTo ?? route.query.redirect)
  const base = (import.meta.env.VITE_API_URL || '/api').replace(/\/$/, '')
  return `${base}/client/auth/google/redirect?volta=${encodeURIComponent(volta)}`
})
</script>

<template>
  <a
    :href="href"
    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-button bg-surface px-5 text-[15px] font-display font-bold tracking-tight text-ink ring-1 ring-line transition duration-150 ease-out hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
  >
    <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true">
      <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.5-5.2 3.5-8.7z" />
      <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z" />
      <path fill="#FBBC05" d="M5.4 14.4A7.2 7.2 0 0 1 5 12c0-.8.1-1.6.4-2.4V6.5H1.4A12 12 0 0 0 0 12c0 1.9.5 3.8 1.4 5.5l4-3.1z" />
      <path fill="#EA4335" d="M12 4.8c1.7 0 3.3.6 4.5 1.8l3.4-3.4A12 12 0 0 0 1.4 6.5l4 3.1C6.3 6.9 8.9 4.8 12 4.8z" />
    </svg>
    {{ t('client.google.continue') }}
  </a>
</template>
