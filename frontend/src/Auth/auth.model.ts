export const TwoFactorChannel = {
  WhatsApp: 'whatsapp',
  Email: 'email',
} as const

export type TwoFactorChannel = (typeof TwoFactorChannel)[keyof typeof TwoFactorChannel]

/** Steps of the login screen. */
export const LoginStep = {
  Credentials: 'credentials',
  Channel: 'channel',
  Code: 'code',
} as const

export type LoginStep = (typeof LoginStep)[keyof typeof LoginStep]

/** Error codes returned by the auth API (translated in the UI). */
export const AuthErrorCode = {
  InvalidCredentials: 'invalidCredentials',
  InvalidCode: 'invalidCode',
  CodeExpired: 'codeExpired',
  TooManyAttempts: 'tooManyAttempts',
  ResendTooSoon: 'resendTooSoon',
  ChallengeExpired: 'challengeExpired',
  SessionExpired: 'sessionExpired',
} as const

export type AuthErrorCode = (typeof AuthErrorCode)[keyof typeof AuthErrorCode]

export class AuthError extends Error {
  constructor(
    public readonly code: AuthErrorCode,
    public readonly attemptsLeft: number | null = null,
  ) {
    super(code)
    this.name = 'AuthError'
  }
}

export interface Credentials {
  email: string
  password: string
}

/** Answer to a correct e-mail + password: the second factor is still needed. */
export interface LoginChallenge {
  challengeId: string
  /** Masked destinations, e.g. "(11) •••••-8888" and "ra•••@linkcut.app". */
  destinations: Record<TwoFactorChannel, string>
}

export interface CodeDelivery {
  channel: TwoFactorChannel
  sentTo: string
  /** Epoch ms. */
  expiresAt: number
  resendAvailableAt: number
  /** Only filled in development, while there is no real WhatsApp/e-mail sender. */
  devCode: string | null
}

export interface SessionUser {
  name: string
  email: string
}

export interface Session {
  accessToken: string
  refreshToken: string
  /** Epoch ms when the access token stops working. */
  accessExpiresAt: number
  /** Epoch ms when the refresh token stops working. */
  refreshExpiresAt: number
  user: SessionUser
}
