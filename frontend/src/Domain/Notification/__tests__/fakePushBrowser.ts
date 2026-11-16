import { vi } from 'vitest'

/**
 * A browser with service workers and the Push API, for tests.
 * `permission` is what Notification.requestPermission answers; `existingKey` is the server key an existing subscription
 * was made with; `subscribeErrors` are thrown by the next subscribe calls, in order.
 */
export function installFakePushBrowser(
  options: {
    permission?: NotificationPermission
    alreadySubscribed?: boolean
    existingKey?: Uint8Array<ArrayBuffer>
    subscribeErrors?: Error[]
  } = {},
) {
  type FakeSubscription = {
    endpoint: string
    options: { applicationServerKey: ArrayBuffer | null }
    toJSON: () => PushSubscriptionJSON
    unsubscribe: () => Promise<boolean>
  }
  let current: FakeSubscription | null = null
  const subscribeErrors = [...(options.subscribeErrors ?? [])]
  let devices = options.alreadySubscribed ? 1 : 0
  const subscription = (endpoint: string, key: BufferSource | null = null): FakeSubscription => ({
    endpoint,
    options: { applicationServerKey: key ? new Uint8Array(key as ArrayBuffer).slice().buffer : null },
    toJSON: () => ({ endpoint, keys: { p256dh: 'BBrowserKey', auth: 'AuthSecret' } }),
    unsubscribe: vi.fn(async () => {
      current = null
      return true
    }),
  })
  if (options.alreadySubscribed) current = subscription('https://push.test/device-1', options.existingKey ?? null)

  const pushManager = {
    getSubscription: vi.fn(async () => current),
    subscribe: vi.fn(async (init?: PushSubscriptionOptionsInit) => {
      const error = subscribeErrors.shift()
      if (error) throw error
      devices += 1
      current = subscription(`https://push.test/device-${devices}`, (init?.applicationServerKey as BufferSource) ?? null)
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
