import type { CodeDelivery, Credentials, LoginChallenge, Session, SessionUser } from './auth.model'
import { TwoFactorChannel } from '@/types/twoFactorChannel'
import { http } from '@/http/client'

/**
 * Identity API (backend: src/Domain/Identity + Orchestrator/SignIn).
 * Sign-in: e-mail + password → code by e-mail or SMS (2FA) → short access token + refresh token.
 * The API speaks snake_case; this file is the only place that maps it to the app's models.
 */

interface UserResponse {
  id: number
  tenant_id: number
  name: string
  email: string
  phone_masked: string | null
  role: string
  two_factor_channel: TwoFactorChannel | null
  available_channels?: TwoFactorChannel[]
}

interface LoginChallengeResponse {
  challenge_id: string
  preferred_channel: TwoFactorChannel | null
  destinations: { channel: TwoFactorChannel; masked: string }[]
}

interface CodeDeliveryResponse {
  channel: TwoFactorChannel
  masked_destination: string
  expires_at: string
  resend_available_at: string
}

export interface SessionResponse {
  access_token: string
  token_type: string
  expires_in: number
  refresh_token: string
  refresh_expires_in: number
  user: UserResponse
}

function toUser(user: UserResponse): SessionUser {
  return {
    id: user.id,
    tenantId: user.tenant_id,
    name: user.name,
    email: user.email,
    phoneMasked: user.phone_masked,
    role: user.role,
    twoFactorChannel: user.two_factor_channel,
    availableChannels: user.available_channels ?? [TwoFactorChannel.Email],
  }
}

/** Also used by the sign-up (it answers with the same session). */
export function toSession(session: SessionResponse, now = Date.now()): Session {
  return {
    accessToken: session.access_token,
    refreshToken: session.refresh_token,
    accessExpiresAt: now + session.expires_in * 1000,
    refreshExpiresAt: now + session.refresh_expires_in * 1000,
    user: toUser(session.user),
  }
}

/** Step 1: e-mail and password. Wrong e-mail or password give the same error (no account discovery). */
/** Step 1, on the barbershop's own page: only its barbers get past the password. */
export async function login(credentials: Credentials, slug: string): Promise<LoginChallenge> {
  const response = await http<LoginChallengeResponse>('/login', { method: 'POST', body: { ...credentials, slug }, auth: false })
  return {
    challengeId: response.data.challenge_id,
    preferredChannel: response.data.preferred_channel,
    destinations: Object.fromEntries(response.data.destinations.map((item) => [item.channel, item.masked])),
  }
}

/** Step 2: sends a new code by the chosen channel (the API enforces the resend wait). */
export async function sendCode(challengeId: string, channel: TwoFactorChannel): Promise<CodeDelivery> {
  const response = await http<CodeDeliveryResponse>('/two-factor/send', {
    method: 'POST',
    body: { challenge_id: challengeId, channel },
    auth: false,
  })
  return {
    channel: response.data.channel,
    sentTo: response.data.masked_destination,
    expiresAt: Date.parse(response.data.expires_at),
    resendAvailableAt: Date.parse(response.data.resend_available_at),
  }
}

/** Step 3: checks the code and opens the session. */
export async function verifyCode(challengeId: string, code: string): Promise<Session> {
  const response = await http<SessionResponse>('/two-factor/verify', {
    method: 'POST',
    body: { challenge_id: challengeId, code },
    auth: false,
  })
  return toSession(response.data)
}

/** Rotates the refresh token: the old one stops working once used. */
export async function refreshSession(refreshToken: string): Promise<Session> {
  const response = await http<SessionResponse>('/token/refresh', {
    method: 'POST',
    body: { refresh_token: refreshToken },
    auth: false,
  })
  return toSession(response.data)
}

export async function logout(refreshToken: string): Promise<void> {
  await http<void>('/logout', { method: 'POST', body: { refresh_token: refreshToken } })
}

export async function fetchMe(): Promise<SessionUser> {
  const response = await http<UserResponse>('/me')
  return toUser(response.data)
}

/** Default 2FA channel (Settings > Segurança); null = ask every time. */
export async function updateTwoFactorChannel(channel: TwoFactorChannel | null): Promise<SessionUser> {
  const response = await http<UserResponse>('/me/two-factor-channel', { method: 'PATCH', body: { channel } })
  return toUser(response.data)
}

/** "Encontrar minha página": e-mails the barber the sign-in link of their page (same answer for any e-mail). */
export async function sendLoginLink(email: string): Promise<void> {
  await http('/login/link', { method: 'POST', body: { email }, auth: false })
}
