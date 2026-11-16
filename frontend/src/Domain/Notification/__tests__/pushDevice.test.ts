import { afterEach, describe, expect, it, vi } from 'vitest'
import { PushDeviceError, keyToBytes, subscribeThisDevice } from '../pushDevice'
import { installFakePushBrowser, removeFakePushBrowser } from './fakePushBrowser'

/** A real VAPID public key shape: 65 bytes, base64url. */
const SERVER_KEY = 'BNoRP7V2noFIQdPQHTU0c7lmNbx1Yl0wF8lE7q-Qn9Vt2yH4xk3tLq8oJ6m1rS0aP5vW2zE9cK7uY4iO3gD1fBw'
const OLD_KEY = 'BOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOldKeyOld'

function domError(name: string, message: string): Error {
  const error = new Error(message)
  error.name = name
  return error
}

describe('subscribeThisDevice', () => {
  afterEach(() => {
    removeFakePushBrowser()
    vi.unstubAllGlobals()
  })

  it('keeps the subscription this browser already has with the server key', async () => {
    // Arrange
    const browser = installFakePushBrowser({ alreadySubscribed: true, existingKey: keyToBytes(SERVER_KEY) })

    // Act
    const result = await subscribeThisDevice(async () => SERVER_KEY)

    // Assert
    expect(result.subscription?.endpoint).toBe('https://push.test/device-1')
    expect(browser.pushManager.subscribe).not.toHaveBeenCalled()
  })

  it('replaces a subscription made with an old server key', async () => {
    // Arrange
    const browser = installFakePushBrowser({ alreadySubscribed: true, existingKey: keyToBytes(OLD_KEY) })

    // Act
    const result = await subscribeThisDevice(async () => SERVER_KEY)

    // Assert
    expect(result.subscription?.endpoint).toBe('https://push.test/device-2')
    expect(browser.pushManager.subscribe).toHaveBeenCalledTimes(1)
  })

  it('when the push service says one is still there (InvalidStateError), drops it and tries once more', async () => {
    // Arrange
    const browser = installFakePushBrowser({ subscribeErrors: [domError('InvalidStateError', 'A subscription with a different key exists')] })

    // Act
    const result = await subscribeThisDevice(async () => SERVER_KEY)

    // Assert
    expect(result.subscription?.endpoint).toBe('https://push.test/device-1')
    expect(browser.pushManager.subscribe).toHaveBeenCalledTimes(2)
  })

  it('names the step that failed, so the console line tells what broke on that browser', async () => {
    // Arrange
    installFakePushBrowser({ subscribeErrors: [domError('AbortError', 'Error retrieving push subscription')] })

    // Act
    const failure = await subscribeThisDevice(async () => SERVER_KEY).catch((caught: unknown) => caught)

    // Assert
    expect(failure).toBeInstanceOf(PushDeviceError)
    expect((failure as PushDeviceError).step).toBe('subscribe')
    expect((failure as Error).message).toBe('subscribe: AbortError: Error retrieving push subscription')
  })
})
