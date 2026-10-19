import { afterEach, describe, expect, it, vi } from 'vitest'
import { http, setHttpAuthHandlers } from './client'
import { ApiError } from './apiError'
import { envelope, installFakeApi } from '@/utils/testFetch'

describe('http client', () => {
  afterEach(() => {
    setHttpAuthHandlers(null)
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('returns the body as data and sends Accept, X-Request-Id and the Bearer token', async () => {
    // Arrange
    const { calls } = installFakeApi({ 'GET /me': () => ({ status: 200, body: { id: 1 } }) })
    setHttpAuthHandlers({ accessToken: () => 'token-1', refresh: vi.fn(), onSessionExpired: vi.fn() })

    // Act
    const response = await http<{ id: number }>('/me')

    // Assert
    expect(response.data).toEqual({ id: 1 })
    expect(calls[0]?.headers).toMatchObject({ Accept: 'application/json', Authorization: 'Bearer token-1' })
    expect(calls[0]?.headers['X-Request-Id']).toBeTruthy()
  })

  it('throws the error envelope as an ApiError', async () => {
    // Arrange
    installFakeApi({ 'POST /login': () => envelope('INVALID_CREDENTIALS', 401) })

    // Act
    const caught = await http('/login', { method: 'POST', body: {}, auth: false }).catch((error: unknown) => error)

    // Assert
    expect(caught).toBeInstanceOf(ApiError)
    expect(caught).toMatchObject({ code: 'INVALID_CREDENTIALS', status: 401, requestId: 'req-test' })
  })

  it('turns a failed connection into NETWORK_ERROR', async () => {
    // Arrange
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    // Act
    const caught = await http('/me').catch((error: unknown) => error)

    // Assert
    expect(caught).toMatchObject({ code: 'NETWORK_ERROR', status: 0 })
  })

  it('refreshes once on 401 and repeats the request with the new token', async () => {
    // Arrange
    let token = 'old'
    const { calls } = installFakeApi({
      'GET /me': (call) =>
        call.headers.Authorization === 'Bearer new' ? { status: 200, body: { id: 1 } } : envelope('UNAUTHENTICATED', 401),
    })
    const refresh = vi.fn(async () => {
      token = 'new'
      return true
    })
    setHttpAuthHandlers({ accessToken: () => token, refresh, onSessionExpired: vi.fn() })

    // Act
    const response = await http<{ id: number }>('/me')

    // Assert
    expect(response.data).toEqual({ id: 1 })
    expect(refresh).toHaveBeenCalledTimes(1)
    expect(calls).toHaveLength(2)
  })

  it('ends the session when the refresh is refused', async () => {
    // Arrange
    installFakeApi({ 'GET /me': () => envelope('UNAUTHENTICATED', 401) })
    const onSessionExpired = vi.fn()
    setHttpAuthHandlers({ accessToken: () => 'old', refresh: vi.fn(async () => false), onSessionExpired })

    // Act
    const caught = await http('/me').catch((error: unknown) => error)

    // Assert
    expect(caught).toMatchObject({ code: 'UNAUTHENTICATED' })
    expect(onSessionExpired).toHaveBeenCalledTimes(1)
  })

  it('never refreshes public calls (login, refresh itself)', async () => {
    // Arrange
    installFakeApi({ 'POST /token/refresh': () => envelope('INVALID_REFRESH_TOKEN', 401) })
    const refresh = vi.fn()
    setHttpAuthHandlers({ accessToken: () => 'old', refresh, onSessionExpired: vi.fn() })

    // Act
    await http('/token/refresh', { method: 'POST', body: {}, auth: false }).catch(() => undefined)

    // Assert
    expect(refresh).not.toHaveBeenCalled()
  })
})
