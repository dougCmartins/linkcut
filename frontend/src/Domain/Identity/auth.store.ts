import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as authApi from './auth.api'
import { LoginStep, type CodeDelivery, type Credentials, type LoginChallenge, type Session, type SessionUser } from './auth.model'
import type { TwoFactorChannel } from '@/types/twoFactorChannel'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { setHttpAuthHandlers } from '@/http/client'
import { ApiError } from '@/http/apiError'
import { useApiErrors } from '@/composables/useApiErrors'
import { setObservabilityUser } from '@/observability/sentry'
import { logSecurityEvent } from '@/utils/logger'

/** Session lives in sessionStorage: closing the browser ends it. */
const SESSION_KEY = 'linkcut.session'

/** Errors that end the login attempt and send the barber back to the first step. */
const RESTART_ERRORS: string[] = [ApiErrorCode.TooManyAttempts, ApiErrorCode.ChallengeExpired, ApiErrorCode.SendLimitReached]

export interface LoginError {
  /** i18n key under `errors.api`. */
  code: string
  attemptsLeft: number | null
}

function readSession(): Session | null {
  try {
    const raw = sessionStorage.getItem(SESSION_KEY)
    return raw ? (JSON.parse(raw) as Session) : null
  } catch {
    return null
  }
}

function writeSession(session: Session | null): void {
  try {
    if (session) sessionStorage.setItem(SESSION_KEY, JSON.stringify(session))
    else sessionStorage.removeItem(SESSION_KEY)
  } catch {
    // Storage blocked: the session lasts while the page is open.
  }
}

export const useAuthStore = defineStore('auth', () => {
  const apiErrors = useApiErrors()
  const session = ref<Session | null>(readSession())
  const step = ref<LoginStep>(LoginStep.Credentials)
  const challenge = ref<LoginChallenge | null>(null)
  const delivery = ref<CodeDelivery | null>(null)
  const isBusy = ref(false)
  const error = ref<LoginError | null>(null)
  let sessionExpiredListener: (() => void) | null = null
  /** One refresh at a time: two parallel refreshes would look like a reused (stolen) token. */
  let refreshing: Promise<boolean> | null = null

  const user = computed(() => session.value?.user ?? null)

  function setSession(next: Session | null): void {
    session.value = next
    writeSession(next)
    setObservabilityUser(next ? { id: next.user.id, tenantId: next.user.tenantId } : null)
  }

  function setUser(next: SessionUser): void {
    if (session.value) setSession({ ...session.value, user: next })
  }

  function hasValidAccess(now = Date.now()): boolean {
    return Boolean(session.value) && session.value!.accessExpiresAt > now
  }

  function resetLogin(): void {
    step.value = LoginStep.Credentials
    challenge.value = null
    delivery.value = null
  }

  /** Runs an API call with the busy flag and turns failures into a translated error. */
  async function run(action: () => Promise<void>, event: string): Promise<boolean> {
    isBusy.value = true
    error.value = null
    try {
      await action()
      return true
    } catch (caught) {
      const code = apiErrors.errorCode(caught)
      const attemptsLeft = apiErrors.attemptsLeft(caught)
      error.value = { code: apiErrors.messageKey(caught), attemptsLeft }
      logSecurityEvent(event, { reason: code, attemptsLeft, requestId: apiErrors.requestId(caught) })
      if (RESTART_ERRORS.includes(code)) resetLogin()
      return false
    } finally {
      isBusy.value = false
    }
  }

  /** With a default channel saved in the settings, the code is sent right away (no channel step). */
  async function submitCredentials(credentials: Credentials): Promise<boolean> {
    const isAccepted = await run(async () => {
      challenge.value = await authApi.login(credentials)
      step.value = LoginStep.Channel
    }, 'login.credentials_rejected')
    const preferred = challenge.value?.preferredChannel
    const canUsePreferred = preferred && challenge.value?.destinations[preferred]
    return isAccepted && canUsePreferred ? requestCode(preferred) : isAccepted
  }

  function requestCode(channel: TwoFactorChannel): Promise<boolean> {
    return run(async () => {
      if (!challenge.value) throw new ApiError(ApiErrorCode.ChallengeExpired, 410)
      delivery.value = await authApi.sendCode(challenge.value.challengeId, channel)
      step.value = LoginStep.Code
    }, 'login.code_not_sent')
  }

  function resendCode(): Promise<boolean> {
    return delivery.value ? requestCode(delivery.value.channel) : Promise.resolve(false)
  }

  function changeChannel(): void {
    error.value = null
    step.value = LoginStep.Channel
  }

  function submitCode(code: string): Promise<boolean> {
    return run(async () => {
      if (!challenge.value) throw new ApiError(ApiErrorCode.ChallengeExpired, 410)
      setSession(await authApi.verifyCode(challenge.value.challengeId, code))
      resetLogin()
    }, 'login.code_rejected')
  }

  function restart(): void {
    error.value = null
    resetLogin()
  }

  async function doRefresh(): Promise<boolean> {
    const current = session.value
    if (!current || current.refreshExpiresAt <= Date.now()) {
      setSession(null)
      return false
    }
    try {
      setSession(await authApi.refreshSession(current.refreshToken))
      return true
    } catch (caught) {
      logSecurityEvent('session.refresh_failed', { reason: apiErrors.errorCode(caught) })
      setSession(null)
      return false
    }
  }

  function refreshAccess(): Promise<boolean> {
    refreshing ??= doRefresh().finally(() => (refreshing = null))
    return refreshing
  }

  /** True when there is a usable session, refreshing the access token if needed. */
  async function ensureSession(): Promise<boolean> {
    return hasValidAccess() || refreshAccess()
  }

  /** Revokes the tokens on the server, then always clears the local session (even offline). */
  async function logout(): Promise<void> {
    const current = session.value
    try {
      if (current) await authApi.logout(current.refreshToken)
    } catch {
      // Offline or already expired: the server tokens expire on their own.
    } finally {
      setSession(null)
      restart()
    }
  }

  /** Opens a session obtained elsewhere (the sign-up answers with one: the e-mail was just confirmed). */
  function startSession(next: Session): void {
    setSession(next)
    resetLogin()
  }

  /** Reloads the user from the API (e.g. the default 2FA channel). */
  async function refreshProfile(): Promise<void> {
    setUser(await authApi.fetchMe())
  }

  async function updateTwoFactorChannel(channel: TwoFactorChannel | null): Promise<void> {
    setUser(await authApi.updateTwoFactorChannel(channel))
  }

  /** Called by the router guard: where to go when the session cannot be recovered mid-page. */
  function onSessionExpired(listener: () => void): void {
    sessionExpiredListener = listener
  }

  setHttpAuthHandlers({
    accessToken: () => session.value?.accessToken ?? null,
    refresh: refreshAccess,
    onSessionExpired: () => {
      setSession(null)
      sessionExpiredListener?.()
    },
  })

  return {
    session,
    user,
    step,
    challenge,
    delivery,
    isBusy,
    error,
    hasValidAccess,
    submitCredentials,
    requestCode,
    resendCode,
    changeChannel,
    submitCode,
    restart,
    ensureSession,
    logout,
    startSession,
    refreshProfile,
    updateTwoFactorChannel,
    onSessionExpired,
  }
})
