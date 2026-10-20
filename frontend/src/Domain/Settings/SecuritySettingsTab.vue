<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import SettingsCard from './SettingsCard.vue'
import PushSwitch from '@/Domain/Notification/PushSwitch.vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { TwoFactorChannel } from '@/types/twoFactorChannel'
import { useApiErrors } from '@/composables/useApiErrors'

/**
 * Default channel for the 2FA code. It belongs to the account on the server
 * (GET /me, PATCH /me/two-factor-channel), so it saves on click, without the save bar.
 */
const ASK_EVERY_TIME = 'ask'

const { t } = useI18n()
const auth = useAuthStore()
const apiErrors = useApiErrors()

const savingId = ref<string | null>(null)
const status = ref<{ kind: 'saved' | 'error'; message: string } | null>(null)

const current = computed(() => auth.user?.twoFactorChannel ?? null)

const options = computed(() => [
  { value: null, id: ASK_EVERY_TIME, label: t('settings.security.askEveryTime'), detail: t('settings.security.askEveryTimeDetail'), disabled: false },
  {
    value: TwoFactorChannel.Sms,
    id: TwoFactorChannel.Sms,
    label: t('auth.channels.sms'),
    detail: auth.user?.phoneMasked ?? t('settings.security.noPhone'),
    disabled: !auth.user?.phoneMasked,
  },
  { value: TwoFactorChannel.Email, id: TwoFactorChannel.Email, label: t('auth.channels.email'), detail: auth.user?.email ?? '…', disabled: false },
])

async function choose(option: (typeof options.value)[number]): Promise<void> {
  if (option.disabled || option.value === current.value || savingId.value) return
  savingId.value = option.id
  status.value = null
  try {
    await auth.updateTwoFactorChannel(option.value)
    status.value = { kind: 'saved', message: t('settings.saved') }
  } catch (caught) {
    status.value = { kind: 'error', message: t(`errors.api.${apiErrors.messageKey(caught)}`) }
  } finally {
    savingId.value = null
  }
}

onMounted(() => {
  // Shows the value saved on the server (it may have changed in another device).
  auth.refreshProfile().catch(() => undefined)
})
</script>

<template>
  <div class="grid gap-5 lg:max-w-2xl">
    <SettingsCard :title="t('settings.security.twoFactorTitle')" :description="t('settings.security.twoFactorDescription')">
      <div role="radiogroup" :aria-label="t('settings.security.twoFactorTitle')" class="grid gap-3" :aria-busy="Boolean(savingId)">
        <button
          v-for="option in options"
          :key="option.id"
          type="button"
          role="radio"
          :aria-checked="current === option.value"
          :disabled="option.disabled || Boolean(savingId)"
          class="flex items-center gap-4 rounded-card bg-raised p-4 text-left ring-1 transition enabled:hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-60"
          :class="current === option.value ? 'ring-2 ring-accent shadow-float' : 'ring-line shadow-soft'"
          @click="choose(option)"
        >
          <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-surface" aria-hidden="true">
            <svg v-if="option.id === TwoFactorChannel.Sms" class="size-5 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="6" y="2.5" width="12" height="19" rx="2.5" />
              <path d="M10 18.5h4" />
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
            :class="current === option.value ? 'bg-accent ring-accent' : 'ring-line'"
            aria-hidden="true"
          >
            <span v-if="current === option.value" class="size-2 rounded-full bg-on-accent" />
          </span>
        </button>
      </div>
      <p
        v-if="status"
        class="text-sm font-semibold"
        :class="status.kind === 'saved' ? 'text-success' : 'text-danger'"
        :role="status.kind === 'error' ? 'alert' : 'status'"
      >
        {{ status.message }}
      </p>
      <p class="text-xs text-muted">{{ t('settings.security.switchHint') }}</p>
    </SettingsCard>

    <SettingsCard :title="t('settings.security.pushTitle')" :description="t('settings.security.pushDescription')">
      <PushSwitch />
    </SettingsCard>
  </div>
</template>
