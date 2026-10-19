import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as authApi from './auth.api'
import {
  AuthError,
  AuthErrorCode,
  LoginStep,
  type CodeDelivery,
  type Credentials,
  type LoginChallenge,
  type Session,
  type TwoFactorChannel,
} from './auth.model'
import { logSecurityEvent } from '@/utils/logger'

/** Session lives in sessionStorage: closing the browser ends it. */
const SESSION_KEY = 'linkcut.session'
const UNEXPECTED_ERROR = 'unexpected'

/** Errors that end the login attempt and send the barber back to the first step. */
const RESTART_ERRORS: string[] = [AuthErrorCode.TooManyAttempts, AuthErrorCode.ChallengeExpired]

export interface LoginError {
  /** i18n key under `auth.errors`. */
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
  const session = ref<Session | null>(readSession())
  const step = ref<LoginStep>(LoginStep.Credentials)
  const challenge = ref<LoginChallenge | null>(null)
  const delivery = ref<CodeDelivery | null>(null)
  const isBusy = ref(false)
  const error = ref<LoginError | null>(null)

  const user = computed(() => session.value?.user ?? null)

  function setSession(next: Session | null): void {
    session.value = next
    writeSession(next)
  }

  function hasValidAccess(now = Date.now()): boolean {
    return session.value !== null && session.value.accessExpiresAt > now
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
      const code = caught instanceof AuthError ? caught.code : UNEXPECTED_ERROR
      const attemptsLeft = caught instanceof AuthError ? caught.attemptsLeft : null
      error.value = { code, attemptsLeft }
      logSecurityEvent(event, { reason: code, attemptsLeft })
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
    return isAccepted && preferred ? requestCode(preferred) : isAccepted
  }

  function requestCode(channel: TwoFactorChannel): Promise<boolean> {
    return run(async () => {
      if (!challenge.value) throw new AuthError(AuthErrorCode.ChallengeExpired)
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
      if (!challenge.value) throw new AuthError(AuthErrorCode.ChallengeExpired)
      setSession(await authApi.verifyCode(challenge.value.challengeId, code))
      resetLogin()
    }, 'login.code_rejected')
  }

  function restart(): void {
    error.value = null
    resetLogin()
  }

  /** True when there is a usable session, refreshing the access token if needed. */
  async function ensureSession(): Promise<boolean> {
    if (hasValidAccess()) return true
    const current = session.value
    if (!current || current.refreshExpiresAt <= Date.now()) {
      setSession(null)
      return false
    }
    try {
      setSession(await authApi.refreshSession(current.refreshToken))
      return true
    } catch {
      logSecurityEvent('session.refresh_failed')
      setSession(null)
      return false
    }
  }

  async function logout(): Promise<void> {
    if (session.value) await authApi.logout(session.value.refreshToken)
    setSession(null)
    restart()
  }

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
  }
})
