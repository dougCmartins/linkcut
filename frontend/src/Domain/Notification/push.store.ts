import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { deletePushSubscription, fetchPushPublicKey, savePushSubscription } from './push.api'
import { PushSupport, SERVICE_WORKER_URL, pushSupport, subscribeThisDevice } from './pushDevice'

export { PushSupport } from './pushDevice'

/** Push notifications on this device for the signed-in barber (e.g. "Novo pedido de agendamento"). */
export const usePushStore = defineStore('push', () => {
  const support = ref<PushSupport>(PushSupport.Checking)
  const permission = ref<NotificationPermission>('default')
  const isSubscribed = ref(false)
  const isBusy = ref(false)
  const hasFailed = ref(false)

  const isBlocked = computed(() => permission.value === 'denied')
  const canEnable = computed(() => support.value === PushSupport.Ready && !isSubscribed.value && !isBlocked.value)

  async function currentSubscription(): Promise<PushSubscription | null> {
    const registration = await navigator.serviceWorker.getRegistration(SERVICE_WORKER_URL)
    return (await registration?.pushManager.getSubscription()) ?? null
  }

  /** Reads what this browser supports and whether it already receives notifications. */
  async function refresh(): Promise<void> {
    support.value = pushSupport()
    if (support.value !== PushSupport.Ready) return
    permission.value = Notification.permission
    isSubscribed.value = permission.value === 'granted' && (await currentSubscription()) !== null
  }

  /** Asks the browser permission, subscribes with the server key and sends the device to the API. */
  async function enable(): Promise<void> {
    isBusy.value = true
    hasFailed.value = false
    try {
      const answer = await subscribeThisDevice(fetchPushPublicKey)
      permission.value = answer.permission
      const subscription = answer.subscription
      if (!subscription) return
      await savePushSubscription(subscription)
      isSubscribed.value = true
    } catch {
      hasFailed.value = true
    } finally {
      isBusy.value = false
    }
  }

  /** This device stops receiving (also on logout, so the next person on this browser doesn't get them). */
  async function disable(): Promise<void> {
    if (support.value !== PushSupport.Ready) return
    isBusy.value = true
    try {
      const subscription = await currentSubscription()
      if (subscription) {
        await deletePushSubscription(subscription.endpoint).catch(() => undefined)
        await subscription.unsubscribe()
      }
      isSubscribed.value = false
    } finally {
      isBusy.value = false
    }
  }

  return { support, permission, isSubscribed, isBusy, hasFailed, isBlocked, canEnable, refresh, enable, disable }
})
