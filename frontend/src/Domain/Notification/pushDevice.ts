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

/** The step of turning notifications on that broke: shown in the console so a browser-specific failure can be traced. */
export type PushStep = 'permission' | 'register' | 'subscribe'

export class PushDeviceError extends Error {
  constructor(
    readonly step: PushStep,
    cause: unknown,
  ) {
    super(`${step}: ${cause instanceof Error ? `${cause.name}: ${cause.message}` : String(cause)}`, { cause })
    this.name = 'PushDeviceError'
  }
}

/** Same bytes? A subscription made with an old server key can't receive our pushes (e.g. after a key rotation). */
function sameKey(current: ArrayBuffer | null | undefined, expected: Uint8Array): boolean {
  if (!current) return true // the browser does not tell (older ones): keep it
  const bytes = new Uint8Array(current)
  return bytes.length === expected.length && bytes.every((byte, index) => byte === expected[index])
}

async function step<T>(name: PushStep, run: () => Promise<T>): Promise<T> {
  try {
    return await run()
  } catch (caught) {
    throw new PushDeviceError(name, caught)
  }
}

/**
 * This browser's subscription with the server key. An old one made with another key is replaced; and when the push
 * service refuses because one is still there (Firefox and Chrome: InvalidStateError), it is dropped and tried again.
 */
async function subscribeWithKey(pushManager: PushManager, serverKey: Uint8Array<ArrayBuffer>): Promise<PushSubscription> {
  const existing = await pushManager.getSubscription()
  if (existing && sameKey(existing.options?.applicationServerKey, serverKey)) return existing
  await existing?.unsubscribe()
  const options = { userVisibleOnly: true, applicationServerKey: serverKey }
  try {
    return await pushManager.subscribe(options)
  } catch (caught) {
    if (!(caught instanceof Error) || caught.name !== 'InvalidStateError') throw caught
    await (await pushManager.getSubscription())?.unsubscribe()
    return pushManager.subscribe(options)
  }
}

/**
 * Asks the browser permission and returns its answer and this browser's subscription (made with the server key when
 * there is none). The subscription is null when the person did not allow notifications.
 * A failure comes as a PushDeviceError that names the step.
 */
export async function subscribeThisDevice(
  publicKey: () => Promise<string>,
): Promise<{ permission: NotificationPermission; subscription: PushSubscriptionJSON | null }> {
  // Called before any other await: Firefox only shows the prompt inside the click.
  const permission = await step('permission', () => Notification.requestPermission())
  if (permission !== 'granted') return { permission, subscription: null }
  const registration = await step('register', async () => {
    await navigator.serviceWorker.register(SERVICE_WORKER_URL)
    return navigator.serviceWorker.ready
  })
  const subscription = await step('subscribe', async () => subscribeWithKey(registration.pushManager, keyToBytes(await publicKey())))
  return { permission, subscription: subscription.toJSON() }
}
