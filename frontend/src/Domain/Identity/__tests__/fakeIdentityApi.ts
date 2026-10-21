import { envelope, installFakeApi, type FakeCall, type FakeHandler } from '@/utils/testFetch'

/** A fake Identity API with the same contract as the backend (snake_case, error envelope). */
export const RIGHT_CODE = '123456'
export const PASSWORD = 'linkcut123'

export const USER = {
  id: 7,
  tenant_id: 3,
  name: 'Rafael Costa',
  email: 'rafael@linkcut.app',
  phone_masked: '+55 •• •••••-7777',
  role: 'owner',
  two_factor_channel: null as string | null,
  /** SMS on in this fake (the real API has it off until there is a provider). */
  available_channels: ['email', 'sms'],
}

export function sessionBody(accessToken = 'access-1', refreshToken = 'refresh-1') {
  return {
    access_token: accessToken,
    token_type: 'Bearer',
    expires_in: 900,
    refresh_token: refreshToken,
    refresh_expires_in: 604800,
    user: USER,
  }
}

export function identityRoutes(options: { preferredChannel?: string | null } = {}): Record<string, FakeHandler> {
  let attemptsLeft = 5
  return {
    'POST /login': (call: FakeCall) =>
      call.body?.password === PASSWORD
        ? {
            status: 201,
            body: {
              challenge_id: '01jchallenge',
              preferred_channel: options.preferredChannel ?? null,
              destinations: [
                { channel: 'email', masked: 'ra****@linkcut.app' },
                { channel: 'sms', masked: '+55 •• •••••-7777' },
              ],
            },
          }
        : envelope('INVALID_CREDENTIALS', 401),
    'POST /two-factor/send': (call: FakeCall) => ({
      status: 201,
      body: {
        channel: call.body?.channel,
        masked_destination: call.body?.channel === 'sms' ? '+55 •• •••••-7777' : 'ra****@linkcut.app',
        expires_at: new Date(Date.now() + 300_000).toISOString(),
        resend_available_at: new Date(Date.now() + 30_000).toISOString(),
      },
    }),
    'POST /two-factor/verify': (call: FakeCall) => {
      if (call.body?.code === RIGHT_CODE) return { status: 201, body: sessionBody() }
      attemptsLeft -= 1
      return envelope('INVALID_CODE', 422, { attempts_left: attemptsLeft })
    },
    'POST /token/refresh': () => ({ status: 201, body: sessionBody('access-2', 'refresh-2') }),
    'POST /logout': () => ({ status: 204 }),
    'GET /me': () => ({ status: 200, body: USER }),
    'PATCH /me/two-factor-channel': (call: FakeCall) => ({ status: 200, body: { ...USER, two_factor_channel: call.body?.channel ?? null } }),
  }
}

export function installFakeIdentityApi(options: { preferredChannel?: string | null } = {}) {
  return installFakeApi(identityRoutes(options))
}
