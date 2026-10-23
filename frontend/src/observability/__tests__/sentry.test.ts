import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createApp } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import { CONSENT_KEY, CONSENT_VERSION } from '@/Legal/consent'

const replay = { stop: vi.fn(), startBuffering: vi.fn() }
const sentry = {
  init: vi.fn(),
  addIntegration: vi.fn(),
  getReplay: vi.fn(() => undefined as typeof replay | undefined),
  browserTracingIntegration: vi.fn(() => ({ name: 'BrowserTracing' })),
  replayIntegration: vi.fn(() => ({ name: 'Replay' })),
  setUser: vi.fn(),
  setTag: vi.fn(),
}
vi.mock('@sentry/vue', () => sentry)

async function freshSentry() {
  vi.resetModules()
  return import('../sentry')
}
const app = () => createApp({})
const router = () => createRouter({ history: createMemoryHistory(), routes: [{ path: '/', component: {} }] })
const sampler = () => (sentry.init.mock.calls.at(-1)![0] as { tracesSampler: () => number }).tracesSampler

describe('Sentry by consent', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.clearAllMocks()
    vi.stubEnv('VITE_SENTRY_DSN', 'https://key@sentry.test/1')
  })

  it('reports errors without tracing or replay before the visitor agrees', async () => {
    // Arrange
    const { initSentry } = await freshSentry()

    // Act
    initSentry(app(), router())

    // Assert
    expect(sentry.init).toHaveBeenCalledWith(expect.objectContaining({ integrations: [], sendDefaultPii: false }))
    expect(sentry.addIntegration).not.toHaveBeenCalled()
    expect(sampler()()).toBe(0)
  })

  it('adds tracing and the error replay when performance was already accepted', async () => {
    // Arrange
    localStorage.setItem(CONSENT_KEY, JSON.stringify({ version: CONSENT_VERSION, performance: true, decidedAt: '2026-10-23T10:00:00Z' }))
    const { initSentry } = await freshSentry()

    // Act
    initSentry(app(), router())

    // Assert
    expect(sentry.browserTracingIntegration).toHaveBeenCalled()
    expect(sentry.replayIntegration).toHaveBeenCalledWith({ maskAllText: true, blockAllMedia: true })
    expect(sentry.addIntegration).toHaveBeenCalledTimes(2)
    expect(sampler()()).toBeGreaterThan(0)
  })

  it('stops the replay and sampling when the consent is withdrawn', async () => {
    // Arrange
    const { initSentry, setPerformanceMonitoring } = await freshSentry()
    initSentry(app(), router())
    setPerformanceMonitoring(true)
    sentry.getReplay.mockReturnValue(replay)

    // Act
    setPerformanceMonitoring(false)

    // Assert
    expect(replay.stop).toHaveBeenCalled()
    expect(sampler()()).toBe(0)
  })
})
