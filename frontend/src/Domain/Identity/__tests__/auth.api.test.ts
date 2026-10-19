import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import {
  CODE_TTL_MS,
  DEMO_ACCOUNT,
  MAX_CODE_ATTEMPTS,
  RESEND_DELAY_MS,
  login,
  maskEmail,
  maskPhone,
  refreshSession,
  sendCode,
  verifyCode,
} from '../auth.api'
import { AuthError, AuthErrorCode, TwoFactorChannel } from '../auth.model'

const demoCredentials = { email: DEMO_ACCOUNT.email, password: DEMO_ACCOUNT.password }

async function errorCodeOf(promise: Promise<unknown>): Promise<string | null> {
  try {
    await promise
    return null
  } catch (error) {
    return error instanceof AuthError ? error.code : 'unknown'
  }
}

async function startChallengeWithCode() {
  const challenge = await login(demoCredentials)
  const delivery = await sendCode(challenge.challengeId, TwoFactorChannel.WhatsApp)
  return { challengeId: challenge.challengeId, code: delivery.devCode ?? '' }
}

describe('auth api (mock)', () => {
  beforeEach(() => {
    sessionStorage.clear()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('rejects a wrong password with the same error as an unknown e-mail', async () => {
    // Arrange
    const wrongPassword = { ...demoCredentials, password: 'wrong-pass' }
    const unknownEmail = { ...demoCredentials, email: 'someone@linkcut.app' }

    // Act
    const errors = await Promise.all([errorCodeOf(login(wrongPassword)), errorCodeOf(login(unknownEmail))])

    // Assert
    expect(errors).toEqual([AuthErrorCode.InvalidCredentials, AuthErrorCode.InvalidCredentials])
  })

  it('asks for a second factor with masked destinations', async () => {
    // Arrange & Act
    const challenge = await login(demoCredentials)

    // Assert
    expect(challenge.destinations).toEqual({ whatsapp: '(11) •••••-8888', email: 'ra•••@linkcut.app' })
  })

  it('signs in with the code that was sent', async () => {
    // Arrange
    const { challengeId, code } = await startChallengeWithCode()

    // Act
    const session = await verifyCode(challengeId, code)

    // Assert
    expect(code).toMatch(/^\d{6}$/)
    expect(session.user.email).toBe(DEMO_ACCOUNT.email)
    expect(session.accessToken).not.toBe(session.refreshToken)
  })

  it('counts down the attempts on a wrong code and ends after the limit', async () => {
    // Arrange
    const { challengeId, code } = await startChallengeWithCode()
    const wrong = code === '000000' ? '111111' : '000000'

    // Act
    const first = await verifyCode(challengeId, wrong).catch((error: AuthError) => error)
    for (let attempt = 2; attempt < MAX_CODE_ATTEMPTS; attempt++) await verifyCode(challengeId, wrong).catch(() => null)
    const last = await errorCodeOf(verifyCode(challengeId, wrong))
    const afterLimit = await errorCodeOf(verifyCode(challengeId, code))

    // Assert
    expect(first).toMatchObject({ code: AuthErrorCode.InvalidCode, attemptsLeft: MAX_CODE_ATTEMPTS - 1 })
    expect(last).toBe(AuthErrorCode.TooManyAttempts)
    expect(afterLimit).toBe(AuthErrorCode.ChallengeExpired)
  })

  it('refuses a code after it expires', async () => {
    // Arrange
    vi.useFakeTimers({ toFake: ['Date'] })
    const { challengeId, code } = await startChallengeWithCode()

    // Act
    vi.setSystemTime(Date.now() + CODE_TTL_MS + 1000)
    const error = await errorCodeOf(verifyCode(challengeId, code))

    // Assert
    expect(error).toBe(AuthErrorCode.CodeExpired)
  })

  it('only allows a resend after the waiting time', async () => {
    // Arrange
    vi.useFakeTimers({ toFake: ['Date'] })
    const challenge = await login(demoCredentials)
    await sendCode(challenge.challengeId, TwoFactorChannel.Email)

    // Act
    const tooSoon = await errorCodeOf(sendCode(challenge.challengeId, TwoFactorChannel.Email))
    vi.setSystemTime(Date.now() + RESEND_DELAY_MS + 1000)
    const later = await errorCodeOf(sendCode(challenge.challengeId, TwoFactorChannel.Email))

    // Assert
    expect(tooSoon).toBe(AuthErrorCode.ResendTooSoon)
    expect(later).toBeNull()
  })

  it('rotates the refresh token: an old one cannot be reused', async () => {
    // Arrange
    const { challengeId, code } = await startChallengeWithCode()
    const session = await verifyCode(challengeId, code)

    // Act
    const renewed = await refreshSession(session.refreshToken)
    const reuse = await errorCodeOf(refreshSession(session.refreshToken))

    // Assert
    expect(renewed.refreshToken).not.toBe(session.refreshToken)
    expect(reuse).toBe(AuthErrorCode.SessionExpired)
  })
})

describe('masking', () => {
  it('hides most of the phone and e-mail', () => {
    // Arrange
    const phone = '5521988887777'
    const email = 'joao.silva@gmail.com'

    // Act
    const masked = [maskPhone(phone), maskEmail(email)]

    // Assert
    expect(masked).toEqual(['(21) •••••-7777', 'jo•••@gmail.com'])
  })
})
