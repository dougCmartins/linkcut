import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { PushSupport, usePushStore } from '../push.store'
import { installFakePushBrowser, removeFakePushBrowser } from './fakePushBrowser'
import { installFakeApi } from '@/utils/testFetch'

/** A real VAPID public key shape: 65 bytes, base64url. */
const PUBLIC_KEY = 'BNoRP7V2noFIQdPQHTU0c7lmNbx1Yl0wF8lE7q-Qn9Vt2yH4xk3tLq8oJ6m1rS0aP5vW2zE9cK7uY4iO3gD1fBw'

function installFakePushApi() {
  return installFakeApi({
    'GET /notifications/push/public-key': () => ({ status: 200, body: { public_key: PUBLIC_KEY } }),
    'POST /notifications/push/subscriptions': () => ({ status: 204 }),
    'DELETE /notifications/push/subscriptions': () => ({ status: 204 }),
  })
}

describe('push store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  afterEach(() => {
    removeFakePushBrowser()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
    vi.restoreAllMocks()
  })

  it('knows when the browser cannot receive notifications', async () => {
    // Arrange
    const store = usePushStore()

    // Act
    await store.refresh()

    // Assert
    expect(store.support).toBe(PushSupport.Unsupported)
    expect(store.canEnable).toBe(false)
  })

  it('on an iPhone outside the home screen app, asks to install it first', async () => {
    // Arrange
    vi.spyOn(navigator, 'userAgent', 'get').mockReturnValue('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
    const store = usePushStore()

    // Act
    await store.refresh()

    // Assert
    expect(store.support).toBe(PushSupport.NeedsInstall)
  })

  it('turns on: asks permission, subscribes with the server key and sends the device to the API', async () => {
    // Arrange
    const browser = installFakePushBrowser()
    const { calls } = installFakePushApi()
    const store = usePushStore()
    await store.refresh()

    // Act
    await store.enable()

    // Assert
    expect(store.isSubscribed).toBe(true)
    expect(browser.serviceWorker.register).toHaveBeenCalledWith('/sw.js')
    const options = browser.pushManager.subscribe.mock.calls[0]?.[0] ?? {}
    expect(options.userVisibleOnly).toBe(true)
    expect((options.applicationServerKey as Uint8Array).length).toBe(65)
    expect(calls.at(-1)).toMatchObject({
      method: 'POST',
      path: '/notifications/push/subscriptions',
      body: { endpoint: 'https://push.test/device-1', keys: { p256dh: 'BBrowserKey', auth: 'AuthSecret' } },
    })
  })

  it('when the person says no, nothing is sent and it shows as blocked', async () => {
    // Arrange
    const browser = installFakePushBrowser({ permission: 'denied' })
    const { calls } = installFakePushApi()
    const store = usePushStore()
    await store.refresh()

    // Act
    await store.enable()

    // Assert
    expect(store.isBlocked).toBe(true)
    expect(store.isSubscribed).toBe(false)
    expect(browser.pushManager.subscribe).not.toHaveBeenCalled()
    expect(calls).toHaveLength(0)
  })

  it('recognizes a device already receiving and turns it off', async () => {
    // Arrange
    const browser = installFakePushBrowser({ alreadySubscribed: true })
    const { calls } = installFakePushApi()
    const store = usePushStore()
    await store.refresh()
    const wasSubscribed = store.isSubscribed

    // Act
    await store.disable()

    // Assert
    expect(wasSubscribed).toBe(true)
    expect(store.isSubscribed).toBe(false)
    expect(browser.current()).toBeNull()
    expect(calls.at(-1)).toMatchObject({ method: 'DELETE', body: { endpoint: 'https://push.test/device-1' } })
  })
})
