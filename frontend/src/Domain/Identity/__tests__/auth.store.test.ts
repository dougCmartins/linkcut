import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '../auth.store'
import { LoginStep } from '../auth.model'
import { installFakeIdentityApi, PASSWORD, RIGHT_CODE } from './fakeIdentityApi'

function expiredAccessSession() {
  const now = Date.now()
  return {
    accessToken: 'access-1',
    refreshToken: 'refresh-1',
    accessExpiresAt: now - 1,
    refreshExpiresAt: now + 60_000,
    user: { id: 7, tenantId: 3, name: 'Rafael', email: 'rafael@linkcut.app', phoneMasked: null, role: 'owner', twoFactorChannel: null },
  }
}

describe('auth store', () => {
  beforeEach(() => {
    sessionStorage.clear()
    localStorage.clear()
    setActivePinia(createPinia())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('refreshes an expired access token only once for parallel callers', async () => {
    // Arrange
    const { calls } = installFakeIdentityApi()
    const auth = useAuthStore()
    auth.session = expiredAccessSession()

    // Act
    const results = await Promise.all([auth.ensureSession(), auth.ensureSession(), auth.ensureSession()])

    // Assert
    expect(results).toEqual([true, true, true])
    expect(calls.filter((call) => call.path === '/token/refresh')).toHaveLength(1)
    expect(auth.session?.accessToken).toBe('access-2')
  })

  it('logout clears the local session even if the API call fails', async () => {
    // Arrange
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('offline')))
    const auth = useAuthStore()
    auth.session = { ...expiredAccessSession(), accessExpiresAt: Date.now() + 60_000 }

    // Act
    await auth.logout()

    // Assert
    expect(auth.session).toBeNull()
    expect(localStorage.getItem('linkcut.session')).toBeNull()
  })

  it('logout revokes the tokens on the server with the current access token', async () => {
    // Arrange
    const { calls } = installFakeIdentityApi()
    const auth = useAuthStore()
    auth.session = { ...expiredAccessSession(), accessExpiresAt: Date.now() + 60_000 }

    // Act
    await auth.logout()

    // Assert
    const logoutCall = calls.find((call) => call.path === '/logout')
    expect(logoutCall?.headers.Authorization).toBe('Bearer access-1')
    expect(logoutCall?.body).toEqual({ refresh_token: 'refresh-1' })
    expect(auth.session).toBeNull()
  })

  it('after the right code the login stays on the code step (no e-mail form flashing before the dashboard)', async () => {
    // Arrange
    installFakeIdentityApi({ preferredChannel: 'email' })
    const auth = useAuthStore()
    await auth.submitCredentials({ email: 'rafael@linkcut.app', password: PASSWORD }, 'rafael')

    // Act
    const signedIn = await auth.submitCode(RIGHT_CODE)

    // Assert
    expect(signedIn).toBe(true)
    expect(auth.step).toBe(LoginStep.Code)
    expect(auth.session?.accessToken).toBe('access-1')
  })

  it('keeps the session after the browser closes (in-app browsers close all the time)', async () => {
    // Arrange
    installFakeIdentityApi({ preferredChannel: 'email' })
    await useAuthStore().submitCredentials({ email: 'rafael@linkcut.app', password: PASSWORD }, 'rafael')
    await useAuthStore().submitCode(RIGHT_CODE)

    // Act: a new page load (new Pinia) reads what was stored.
    sessionStorage.clear()
    setActivePinia(createPinia())

    // Assert
    expect(useAuthStore().session?.refreshToken).toBe('refresh-1')
  })
})
