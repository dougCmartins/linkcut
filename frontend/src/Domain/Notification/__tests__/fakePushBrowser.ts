import { vi } from 'vitest'

/**
 * A browser with service workers and the Push API, for tests.
 * `permission` is what Notification.requestPermission answers.
 */
export function installFakePushBrowser(options: { permission?: NotificationPermission; alreadySubscribed?: boolean } = {}) {
  let current: { endpoint: string; toJSON: () => PushSubscriptionJSON; unsubscribe: () => Promise<boolean> } | null = null
  const subscription = (endpoint: string) => ({
    endpoint,
    toJSON: () => ({ endpoint, keys: { p256dh: 'BBrowserKey', auth: 'AuthSecret' } }),
    unsubscribe: vi.fn(async () => {
      current = null
      return true
    }),
  })
  if (options.alreadySubscribed) current = subscription('https://push.test/device-1')

  const pushManager = {
    getSubscription: vi.fn(async () => current),
    subscribe: vi.fn(async (_options?: PushSubscriptionOptionsInit) => {
      current = subscription('https://push.test/device-1')
      return current
    }),
  }
  const registration = { pushManager }
  const serviceWorker = {
    register: vi.fn(async () => registration),
    getRegistration: vi.fn(async () => registration),
    ready: Promise.resolve(registration),
  }
  const notification = {
    permission: options.alreadySubscribed ? 'granted' : 'default',
    requestPermission: vi.fn(async () => options.permission ?? 'granted'),
  }

  Object.defineProperty(navigator, 'serviceWorker', { value: serviceWorker, configurable: true })
  vi.stubGlobal('PushManager', function PushManager() {})
  vi.stubGlobal('Notification', notification)

  return { serviceWorker, pushManager, notification, current: () => current }
}

export function removeFakePushBrowser(): void {
  Reflect.deleteProperty(navigator, 'serviceWorker')
}
