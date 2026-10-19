<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { TwoFactorChannel } from '@/types/twoFactorChannel'

const props = defineProps<{ destinations: Partial<Record<TwoFactorChannel, string>>; isBusy: boolean }>()
const emit = defineEmits<{ choose: [channel: TwoFactorChannel]; back: [] }>()
const { t } = useI18n()

/** Only the channels this account can use (no SMS without a phone). */
const channels = computed(() =>
  [TwoFactorChannel.Sms, TwoFactorChannel.Email].filter((channel) => Boolean(props.destinations[channel])),
)
</script>

<template>
  <div class="grid gap-3">
    <button
      v-for="channel in channels"
      :key="channel"
      type="button"
      :disabled="isBusy"
      class="flex items-center gap-4 rounded-card bg-surface p-4 text-left ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 hover:ring-accent disabled:opacity-50"
      @click="emit('choose', channel)"
    >
      <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-raised" aria-hidden="true">
        <svg v-if="channel === TwoFactorChannel.Sms" class="size-6 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <rect x="6" y="2.5" width="12" height="19" rx="2.5" />
          <path d="M10 18.5h4" />
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
