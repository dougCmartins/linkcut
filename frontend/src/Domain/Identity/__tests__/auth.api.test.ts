import { afterEach, describe, expect, it, vi } from 'vitest'
import { login, logout, refreshSession, sendCode, updateTwoFactorChannel, verifyCode } from '../auth.api'
import { installFakeIdentityApi, PASSWORD, RIGHT_CODE } from './fakeIdentityApi'

describe('auth api', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('login sends the credentials and maps the challenge', async () => {
    // Arrange
    const { calls } = installFakeIdentityApi({ preferredChannel: 'sms' })

    // Act
    const challenge = await login({ email: 'rafael@linkcut.app', password: PASSWORD }, 'rafael')

    // Assert
    expect(calls[0]?.body).toEqual({ email: 'rafael@linkcut.app', password: PASSWORD, slug: 'rafael' })
    expect(challenge).toEqual({
      challengeId: '01jchallenge',
      preferredChannel: 'sms',
      destinations: { email: 'ra****@linkcut.app', sms: '+55 •• •••••-7777' },
    })
  })

  it('sendCode posts snake_case and turns the dates into epoch ms', async () => {
    // Arrange
    const { calls } = installFakeIdentityApi()
    const before = Date.now()

    // Act
    const delivery = await sendCode('01jchallenge', 'sms')

    // Assert
    expect(calls[0]?.body).toEqual({ challenge_id: '01jchallenge', channel: 'sms' })
    expect(delivery.sentTo).toBe('+55 •• •••••-7777')
    expect(delivery.resendAvailableAt).toBeGreaterThan(before)
    expect(delivery.expiresAt).toBeGreaterThan(delivery.resendAvailableAt)
  })

  it('verifyCode returns the session with absolute expiry times and the user', async () => {
    // Arrange
    installFakeIdentityApi()
    const before = Date.now()

    // Act
    const session = await verifyCode('01jchallenge', RIGHT_CODE)

    // Assert
    expect(session.accessToken).toBe('access-1')
    expect(session.accessExpiresAt).toBeGreaterThanOrEqual(before + 900_000)
    expect(session.refreshExpiresAt).toBeGreaterThanOrEqual(before + 604_800_000)
    expect(session.user).toMatchObject({ id: 7, tenantId: 3, phoneMasked: '+55 •• •••••-7777' })
  })

  it('refresh, logout and channel update call the right endpoints', async () => {
    // Arrange
    const { calls } = installFakeIdentityApi()

    // Act
    const refreshed = await refreshSession('refresh-1')
    await logout('refresh-2')
    const user = await updateTwoFactorChannel('email')

    // Assert
    expect(refreshed.refreshToken).toBe('refresh-2')
    expect(calls.map((call) => `${call.method} ${call.path}`)).toEqual([
      'POST /token/refresh',
      'POST /logout',
      'PATCH /me/two-factor-channel',
    ])
    expect(calls[1]?.body).toEqual({ refresh_token: 'refresh-2' })
    expect(user.twoFactorChannel).toBe('email')
  })
})
