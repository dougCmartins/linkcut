/**
 * This browser and push notifications, shared by the barber's and the client's notices: what it supports, and its
 * subscription (one per browser; the API keeps a row per role).
 */
export const PushSupport = {
  Checking: 'checking',
  /** No service worker / Push API (old browser, or iPhone Safari in a normal tab). */
  Unsupported: 'unsupported',
  /** iPhone/iPad: works only from the home screen app (Share → Add to Home Screen). */
  NeedsInstall: 'needsInstall',
  Ready: 'ready',
} as const

export type PushSupport = (typeof PushSupport)[keyof typeof PushSupport]

export const SERVICE_WORKER_URL = '/sw.js'

/** The VAPID key comes as base64url; the browser wants the bytes. */
export function keyToBytes(base64Url: string): Uint8Array<ArrayBuffer> {
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

export function pushSupport(): PushSupport {
  const hasApi = typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window
  if (hasApi) return PushSupport.Ready
  return isAppleMobile() && !isInstalledApp() ? PushSupport.NeedsInstall : PushSupport.Unsupported
}

/**
 * Asks the browser permission and returns its answer and this browser's subscription (made with the server key when
 * there is none). The subscription is null when the person did not allow notifications.
 */
export async function subscribeThisDevice(
  publicKey: () => Promise<string>,
): Promise<{ permission: NotificationPermission; subscription: PushSubscriptionJSON | null }> {
  const permission = await Notification.requestPermission()
  if (permission !== 'granted') return { permission, subscription: null }
  const registration = await navigator.serviceWorker.register(SERVICE_WORKER_URL)
  await navigator.serviceWorker.ready
  const subscription =
    (await registration.pushManager.getSubscription()) ??
    (await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyToBytes(await publicKey()) }))
  return { permission, subscription: subscription.toJSON() }
}
