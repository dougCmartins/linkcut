import type { TwoFactorChannel } from '@/types/twoFactorChannel'

export { TwoFactorChannel } from '@/types/twoFactorChannel'

/** Steps of the login screen. */
export const LoginStep = {
  Credentials: 'credentials',
  Channel: 'channel',
  Code: 'code',
} as const

export type LoginStep = (typeof LoginStep)[keyof typeof LoginStep]

export interface Credentials {
  email: string
  password: string
}

/** Answer to a correct e-mail + password: the second factor is still needed. */
export interface LoginChallenge {
  challengeId: string
  /** Masked destinations by channel; SMS is missing when the account has no phone. */
  destinations: Partial<Record<TwoFactorChannel, string>>
  /** Default channel chosen in Settings > Segurança; null = ask every time. */
  preferredChannel: TwoFactorChannel | null
}

export interface CodeDelivery {
  channel: TwoFactorChannel
  sentTo: string
  /** Epoch ms. */
  expiresAt: number
  resendAvailableAt: number
}

export interface SessionUser {
  id: number
  tenantId: number
  name: string
  email: string
  phoneMasked: string | null
  role: string
  twoFactorChannel: TwoFactorChannel | null
  /** Channels the code can go by (SMS only when it is on in the API and the user has a phone). Missing = e-mail. */
  availableChannels?: TwoFactorChannel[]
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
