<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import { PushSupport, pushSupport, subscribeThisDevice } from '@/Domain/Notification/pushDevice'
import { fetchClientPushKey, saveClientPushSubscription } from './client.api'

/**
 * "Avisar neste aparelho": the client gets the barbershop's answer (and the reminder) as a notification, not only
 * by e-mail. `pushUrl`: the signed link of a booking just made; without it, the signed-in client's session is used.
 */
const props = withDefaults(defineProps<{ pushUrl?: string | null }>(), { pushUrl: null })
const { t } = useI18n()

type State = 'idle' | 'busy' | 'on' | 'denied' | 'failed'
const support = pushSupport()
const state = ref<State>(typeof Notification !== 'undefined' && Notification.permission === 'denied' ? 'denied' : 'idle')

async function enable(): Promise<void> {
  state.value = 'busy'
  try {
    const { permission, subscription } = await subscribeThisDevice(fetchClientPushKey)
    if (!subscription) return void (state.value = permission === 'denied' ? 'denied' : 'idle')
    await saveClientPushSubscription(subscription, props.pushUrl)
    state.value = 'on'
  } catch (caught) {
    // Shown in the browser console so a failure on a given browser can be traced (no personal data in it).
    console.warn('client_push_failed', caught instanceof Error ? `${caught.name}: ${caught.message}` : caught)
    state.value = 'failed'
  }
}
</script>

<template>
  <section v-if="support !== PushSupport.Unsupported" class="w-full rounded-card bg-surface p-4 text-left ring-1 ring-line shadow-soft">
    <p class="font-bold">{{ t('clientPush.title') }}</p>
    <p class="mt-1 text-sm text-muted">{{ t('clientPush.text') }}</p>
    <p v-if="support === PushSupport.NeedsInstall" class="mt-3 text-sm font-semibold">{{ t('clientPush.needsInstall') }}</p>
    <p v-else-if="state === 'on'" class="mt-3 text-sm font-bold text-success" role="status">{{ t('clientPush.on') }}</p>
    <p v-else-if="state === 'denied'" class="mt-3 text-sm font-semibold text-muted" role="status">{{ t('clientPush.denied') }}</p>
    <template v-else>
      <p v-if="state === 'failed'" class="mt-3 text-sm font-semibold text-danger" role="alert">{{ t('clientPush.failed') }}</p>
      <BaseButton class="mt-3" variant="primary" block :disabled="state === 'busy'" @click="enable">
        {{ state === 'busy' ? t('clientPush.enabling') : t('clientPush.enable') }}
      </BaseButton>
    </template>
  </section>
</template>
