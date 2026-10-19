/** Channels for the 2FA code (same values as the API). */
export const TwoFactorChannel = {
  Sms: 'sms',
  Email: 'email',
} as const

export type TwoFactorChannel = (typeof TwoFactorChannel)[keyof typeof TwoFactorChannel]
