import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { deletePushSubscription, fetchPushPublicKey, savePushSubscription } from './push.api'

/** What this browser can do about push notifications. */
export const PushSupport = {
  Checking: 'checking',
  /** No service worker / Push API (old browser, or iPhone Safari in a normal tab). */
  Unsupported: 'unsupported',
  /** iPhone/iPad: works only from the home screen app (Share → Add to Home Screen). */
  NeedsInstall: 'needsInstall',
  Ready: 'ready',
} as const

export type PushSupport = (typeof PushSupport)[keyof typeof PushSupport]

const SERVICE_WORKER_URL = '/sw.js'

/** The VAPID key comes as base64url; the browser wants the bytes. */
function keyToBytes(base64Url: string): Uint8Array<ArrayBuffer> {
  const base64 = (base64Url + '='.repeat((4 - (base64Url.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  const bytes = new Uint8Array(new ArrayBuffer(raw.length))
  for (let index = 0; index < raw.length; index++) bytes[index] = raw.charCodeAt(index)
  return bytes
}

function isAppleMobile(): boolean {
  return /iPhone|iPad|iPod/.test(navigator.userAgent)
}

function isInstalledApp(): boolean {
  return window.matchMedia?.('(display-mode: standalone)').matches || (navigator as { standalone?: boolean }).standalone === true
}

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
    const hasApi = typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window
    if (!hasApi) {
      support.value = isAppleMobile() && !isInstalledApp() ? PushSupport.NeedsInstall : PushSupport.Unsupported
      return
    }
    support.value = PushSupport.Ready
    permission.value = Notification.permission
    isSubscribed.value = permission.value === 'granted' && (await currentSubscription()) !== null
  }

  /** Asks the browser permission, subscribes with the server key and sends the device to the API. */
  async function enable(): Promise<void> {
    isBusy.value = true
    hasFailed.value = false
    try {
      permission.value = await Notification.requestPermission()
      if (permission.value !== 'granted') return
      const registration = await navigator.serviceWorker.register(SERVICE_WORKER_URL)
      await navigator.serviceWorker.ready
      const subscription =
        (await registration.pushManager.getSubscription()) ??
        (await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: keyToBytes(await fetchPushPublicKey()),
        }))
      await savePushSubscription(subscription.toJSON())
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
