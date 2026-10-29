import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'
import { render } from '@testing-library/vue'
import { LIVE_REFRESH_INTERVAL_MS, PUSH_MESSAGE_TYPE, useLiveRefresh } from './useLiveRefresh'

function mountWith(refresh: () => Promise<void>, isBusy: () => boolean = () => false) {
  return render(
    defineComponent({
      setup() {
        useLiveRefresh(refresh, { isBusy })
        return () => h('div')
      },
    }),
  )
}

function setVisibility(state: DocumentVisibilityState) {
  Object.defineProperty(document, 'visibilityState', { value: state, configurable: true })
}

describe('useLiveRefresh', () => {
  let workerTarget: EventTarget

  beforeEach(() => {
    vi.useFakeTimers()
    setVisibility('visible')
    workerTarget = new EventTarget()
    Object.defineProperty(navigator, 'serviceWorker', { value: workerTarget, configurable: true })
  })

  afterEach(() => {
    vi.useRealTimers()
    setVisibility('visible')
  })

  it('refreshes every 30 seconds while the screen is visible, and stops when hidden', async () => {
    // Arrange
    const refresh = vi.fn(() => Promise.resolve())
    mountWith(refresh)

    // Act
    await vi.advanceTimersByTimeAsync(LIVE_REFRESH_INTERVAL_MS)
    setVisibility('hidden')
    await vi.advanceTimersByTimeAsync(LIVE_REFRESH_INTERVAL_MS * 2)

    // Assert
    expect(refresh).toHaveBeenCalledTimes(1)
  })

  it('refreshes right away when a push reaches this browser', async () => {
    // Arrange
    const refresh = vi.fn(() => Promise.resolve())
    mountWith(refresh)

    // Act
    workerTarget.dispatchEvent(new MessageEvent('message', { data: { type: PUSH_MESSAGE_TYPE, tag: 'booking' } }))
    workerTarget.dispatchEvent(new MessageEvent('message', { data: { type: 'something-else' } }))
    await vi.advanceTimersByTimeAsync(0)

    // Assert
    expect(refresh).toHaveBeenCalledTimes(1)
  })

  it('refreshes when the app comes back to the front, but not twice in a row', async () => {
    // Arrange
    const refresh = vi.fn(() => Promise.resolve())
    mountWith(refresh)
    await vi.advanceTimersByTimeAsync(10_000)

    // Act
    document.dispatchEvent(new Event('visibilitychange'))
    window.dispatchEvent(new Event('focus'))
    await vi.advanceTimersByTimeAsync(0)

    // Assert
    expect(refresh).toHaveBeenCalledTimes(1)
  })

  it('skips a round while the user is in the middle of an action', async () => {
    // Arrange
    const refresh = vi.fn(() => Promise.resolve())
    let busy = true
    mountWith(refresh, () => busy)

    // Act
    await vi.advanceTimersByTimeAsync(LIVE_REFRESH_INTERVAL_MS)
    busy = false
    await vi.advanceTimersByTimeAsync(LIVE_REFRESH_INTERVAL_MS)

    // Assert
    expect(refresh).toHaveBeenCalledTimes(1)
  })
})
