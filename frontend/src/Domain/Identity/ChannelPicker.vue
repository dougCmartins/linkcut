<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { TwoFactorChannel } from './auth.model'

defineProps<{ destinations: Record<TwoFactorChannel, string>; isBusy: boolean }>()
const emit = defineEmits<{ choose: [channel: TwoFactorChannel]; back: [] }>()
const { t } = useI18n()

const CHANNELS = [TwoFactorChannel.WhatsApp, TwoFactorChannel.Email]
</script>

<template>
  <div class="grid gap-3">
    <button
      v-for="channel in CHANNELS"
      :key="channel"
      type="button"
      :disabled="isBusy"
      class="flex items-center gap-4 rounded-card bg-surface p-4 text-left ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 hover:ring-accent disabled:opacity-50"
      @click="emit('choose', channel)"
    >
      <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-raised" aria-hidden="true">
        <svg v-if="channel === TwoFactorChannel.WhatsApp" class="size-6 text-success" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 2 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2Z" />
        </svg>
        <svg v-else class="size-6 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <rect x="3" y="5" width="18" height="14" rx="2.5" />
          <path d="m4 7 8 6 8-6" />
        </svg>
      </span>
      <span class="min-w-0 flex-1">
        <span class="block font-display font-extrabold tracking-tight">{{ t(`auth.channels.${channel}`) }}</span>
        <span class="block truncate text-sm text-muted">{{ destinations[channel] }}</span>
      </span>
      <svg class="size-5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
        <path d="m9 6 6 6-6 6" />
      </svg>
    </button>

    <button type="button" class="mt-2 text-sm font-semibold text-muted hover:text-ink" @click="emit('back')">
      {{ t('auth.useOtherAccount') }}
    </button>
  </div>
</template>
