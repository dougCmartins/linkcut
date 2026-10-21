import { envelope, installFakeApi, type FakeCall, type FakeHandler } from '@/utils/testFetch'
import { sessionBody } from '@/Domain/Identity/__tests__/fakeIdentityApi'

/** A fake sign-up API with the same contract as the backend. */
export const SIGN_UP_CODE = '246810'
export const TAKEN_SLUGS = ['navalha']

export function onboardingRoutes(): Record<string, FakeHandler> {
  return {
    'POST /sign-up/code': () => ({ status: 201, body: { expires_in: 900, resend_after: 30 } }),
    'GET /sign-up/slug': ({ query }: FakeCall) => {
      const slug = query.get('slug') ?? ''
      const taken = TAKEN_SLUGS.includes(slug)
      return { status: 200, body: { slug, available: !taken, suggestion: taken ? `${slug}-2` : slug } }
    },
    'POST /sign-up': ({ body }: FakeCall) => {
      if (body?.code !== SIGN_UP_CODE) return envelope('INVALID_CODE', 422, { attempts_left: 4 })
      if (TAKEN_SLUGS.includes(String(body?.slug))) return envelope('SLUG_UNAVAILABLE', 409)
      return { status: 201, body: sessionBody('new-access', 'new-refresh') }
    },
  }
}

export function installFakeOnboardingApi(extraRoutes: Record<string, FakeHandler> = {}) {
  return installFakeApi({ ...onboardingRoutes(), ...extraRoutes })
}
