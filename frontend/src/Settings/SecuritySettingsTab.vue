<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import SettingsCard from './SettingsCard.vue'
import { useSettingsStore } from './settings.store'
import { getTwoFactorDestinations } from '@/Auth/auth.api'
import { TwoFactorChannel } from '@/Auth/auth.model'

const ASK_EVERY_TIME = 'ask'

const { t } = useI18n()
const store = useSettingsStore()
const security = computed(() => store.drafts.security)
const destinations = ref<Record<TwoFactorChannel, string> | null>(null)

const options = computed(() => [
  { value: null, id: ASK_EVERY_TIME, label: t('settings.security.askEveryTime'), detail: t('settings.security.askEveryTimeDetail') },
  ...[TwoFactorChannel.WhatsApp, TwoFactorChannel.Email].map((channel) => ({
    value: channel,
    id: channel,
    label: t(`auth.channels.${channel}`),
    detail: destinations.value?.[channel] ?? '…',
  })),
])

onMounted(async () => {
  destinations.value = await getTwoFactorDestinations()
})
</script>

<template>
  <div class="grid gap-5 lg:max-w-2xl">
    <SettingsCard :title="t('settings.security.twoFactorTitle')" :description="t('settings.security.twoFactorDescription')">
      <div role="radiogroup" :aria-label="t('settings.security.twoFactorTitle')" class="grid gap-3">
        <button
          v-for="option in options"
          :key="option.id"
          type="button"
          role="radio"
          :aria-checked="security.defaultTwoFactorChannel === option.value"
          class="flex items-center gap-4 rounded-card bg-raised p-4 text-left ring-1 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          :class="security.defaultTwoFactorChannel === option.value ? 'ring-2 ring-accent shadow-float' : 'ring-line shadow-soft'"
          @click="security.defaultTwoFactorChannel = option.value"
        >
          <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-surface" aria-hidden="true">
            <svg v-if="option.id === TwoFactorChannel.WhatsApp" class="size-5 text-success" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 2 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2Z" />
            </svg>
            <svg v-else-if="option.id === TwoFactorChannel.Email" class="size-5 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="3" y="5" width="18" height="14" rx="2.5" />
              <path d="m4 7 8 6 8-6" />
            </svg>
            <svg v-else class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9" />
              <path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5V14M12 17h.01" />
            </svg>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block font-display font-extrabold tracking-tight">{{ option.label }}</span>
            <span class="block truncate text-sm text-muted">{{ option.detail }}</span>
          </span>
          <span
            class="flex size-5 shrink-0 items-center justify-center rounded-full ring-2"
            :class="security.defaultTwoFactorChannel === option.value ? 'bg-accent ring-accent' : 'ring-line'"
            aria-hidden="true"
          >
            <span v-if="security.defaultTwoFactorChannel === option.value" class="size-2 rounded-full bg-on-accent" />
          </span>
        </button>
      </div>
      <p class="text-xs text-muted">{{ t('settings.security.switchHint') }}</p>
    </SettingsCard>
  </div>
</template>
