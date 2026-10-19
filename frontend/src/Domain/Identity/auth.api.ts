import {
  AuthError,
  AuthErrorCode,
  TwoFactorChannel,
  type CodeDelivery,
  type Credentials,
  type LoginChallenge,
  type Session,
} from './auth.model'
import { CODE_LENGTH } from './auth.schemas'
import { loadSettings } from '@/Settings/settings.api'

/**
 * MOCK of the authentication API, until the backend exists.
 * The rules live here (the backend will enforce the same ones):
 * - e-mail + password, then a 6-digit code sent by WhatsApp or e-mail (2FA);
 * - the code is valid for 5 minutes, can be resent after 30 seconds, 5 wrong tries end the attempt;
 * - short access token (15 min) + refresh token (7 days) that is rotated on every use.
 * The UI only talks to these functions, so swapping them for HTTP calls changes nothing else.
 */

export const CODE_TTL_MS = 5 * 60_000
export const RESEND_DELAY_MS = 30_000
export const MAX_CODE_ATTEMPTS = 5
const CHALLENGE_TTL_MS = 10 * 60_000
const ACCESS_TTL_MS = 15 * 60_000
const REFRESH_TTL_MS = 7 * 24 * 60 * 60_000
const LATENCY_MS = import.meta.env.MODE === 'test' ? 0 : 450
const REFRESH_STORE_KEY = 'linkcut.mockAuth.refreshTokens'

/** Demo account shown on the login screen in development. */
export const DEMO_ACCOUNT = {
  name: 'Rafael Costa',
  email: 'rafael@linkcut.app',
  password: 'linkcut123',
  phone: '11999998888',
} as const

interface PendingChallenge {
  expiresAt: number
  code: string | null
  codeExpiresAt: number
  resendAvailableAt: number
  attemptsLeft: number
}

const challenges = new Map<string, PendingChallenge>()

const wait = () => new Promise((resolve) => setTimeout(resolve, LATENCY_MS))

function randomHex(bytes: number): string {
  const values = crypto.getRandomValues(new Uint8Array(bytes))
  return Array.from(values, (value) => value.toString(16).padStart(2, '0')).join('')
}

function randomCode(): string {
  const [value = 0] = crypto.getRandomValues(new Uint32Array(1))
  return String(value % 10 ** CODE_LENGTH).padStart(CODE_LENGTH, '0')
}

export function maskPhone(phone: string): string {
  const digits = phone.replace(/\D/g, '').slice(-11)
  return `(${digits.slice(0, 2)}) •••••-${digits.slice(-4)}`
}

export function maskEmail(email: string): string {
  const [name = '', domain = ''] = email.split('@')
  return `${name.slice(0, 2)}•••@${domain}`
}

function readRefreshTokens(): Record<string, number> {
  try {
    return JSON.parse(sessionStorage.getItem(REFRESH_STORE_KEY) ?? '{}') as Record<string, number>
  } catch {
    return {}
  }
}

function writeRefreshTokens(tokens: Record<string, number>): void {
  try {
    sessionStorage.setItem(REFRESH_STORE_KEY, JSON.stringify(tokens))
  } catch {
    // Storage blocked: refresh only works while the page stays open.
  }
}

function issueSession(): Session {
  const now = Date.now()
  const refreshToken = randomHex(24)
  writeRefreshTokens({ ...readRefreshTokens(), [refreshToken]: now + REFRESH_TTL_MS })
  return {
    accessToken: randomHex(24),
    refreshToken,
    accessExpiresAt: now + ACCESS_TTL_MS,
    refreshExpiresAt: now + REFRESH_TTL_MS,
    user: { name: DEMO_ACCOUNT.name, email: DEMO_ACCOUNT.email },
  }
}

function findChallenge(challengeId: string): PendingChallenge {
  const challenge = challenges.get(challengeId)
  if (!challenge || challenge.expiresAt < Date.now()) {
    challenges.delete(challengeId)
    throw new AuthError(AuthErrorCode.ChallengeExpired)
  }
  return challenge
}

/** Step 1: e-mail and password. The same error for wrong e-mail or wrong password (no account discovery). */
export async function login(credentials: Credentials): Promise<LoginChallenge> {
  await wait()
  const isValid =
    credentials.email.trim().toLowerCase() === DEMO_ACCOUNT.email && credentials.password === DEMO_ACCOUNT.password
  if (!isValid) throw new AuthError(AuthErrorCode.InvalidCredentials)

  const challengeId = randomHex(16)
  challenges.set(challengeId, {
    expiresAt: Date.now() + CHALLENGE_TTL_MS,
    code: null,
    codeExpiresAt: 0,
    resendAvailableAt: 0,
    attemptsLeft: MAX_CODE_ATTEMPTS,
  })
  return {
    challengeId,
    destinations: maskedDestinations(),
    // Mock of the account profile on the server: read from the barber's saved settings.
    preferredChannel: loadSettings().security.defaultTwoFactorChannel,
  }
}

function maskedDestinations(): Record<TwoFactorChannel, string> {
  return {
    [TwoFactorChannel.WhatsApp]: maskPhone(DEMO_ACCOUNT.phone),
    [TwoFactorChannel.Email]: maskEmail(DEMO_ACCOUNT.email),
  }
}

/** Masked 2FA destinations of the signed-in account (shown in Settings > Segurança). */
export async function getTwoFactorDestinations(): Promise<Record<TwoFactorChannel, string>> {
  return maskedDestinations()
}

/** Step 2: send a new code by the chosen channel. Respects the resend wait. */
export async function sendCode(challengeId: string, channel: TwoFactorChannel): Promise<CodeDelivery> {
  await wait()
  const challenge = findChallenge(challengeId)
  const now = Date.now()
  if (challenge.code && now < challenge.resendAvailableAt) throw new AuthError(AuthErrorCode.ResendTooSoon)
  challenge.code = randomCode()
  challenge.codeExpiresAt = now + CODE_TTL_MS
  challenge.resendAvailableAt = now + RESEND_DELAY_MS
  challenge.attemptsLeft = MAX_CODE_ATTEMPTS
  return {
    channel,
    sentTo: channel === TwoFactorChannel.WhatsApp ? maskPhone(DEMO_ACCOUNT.phone) : maskEmail(DEMO_ACCOUNT.email),
    expiresAt: challenge.codeExpiresAt,
    resendAvailableAt: challenge.resendAvailableAt,
    devCode: import.meta.env.DEV ? challenge.code : null,
  }
}

/** Step 3: check the code. Returns the session on success. */
export async function verifyCode(challengeId: string, code: string): Promise<Session> {
  await wait()
  const challenge = findChallenge(challengeId)
  if (!challenge.code || Date.now() > challenge.codeExpiresAt) throw new AuthError(AuthErrorCode.CodeExpired)

  if (code !== challenge.code) {
    challenge.attemptsLeft -= 1
    if (challenge.attemptsLeft <= 0) {
      challenges.delete(challengeId)
      throw new AuthError(AuthErrorCode.TooManyAttempts)
    }
    throw new AuthError(AuthErrorCode.InvalidCode, challenge.attemptsLeft)
  }

  challenges.delete(challengeId)
  return issueSession()
}

/** Rotates the refresh token: the old one stops working once used. */
export async function refreshSession(refreshToken: string): Promise<Session> {
  await wait()
  const tokens = readRefreshTokens()
  const expiresAt = tokens[refreshToken]
  delete tokens[refreshToken]
  writeRefreshTokens(tokens)
  if (!expiresAt || expiresAt < Date.now()) throw new AuthError(AuthErrorCode.SessionExpired)
  return issueSession()
}

export async function logout(refreshToken: string): Promise<void> {
  const tokens = readRefreshTokens()
  delete tokens[refreshToken]
  writeRefreshTokens(tokens)
}
