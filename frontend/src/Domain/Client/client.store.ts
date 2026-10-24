import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as clientApi from './client.api'
import type { ClientAppointment, ClientProfile, ClientSession } from './client.model'
import { setHttpAuthHandlers } from '@/http/client'
import { useApiErrors } from '@/composables/useApiErrors'
import { ApiErrorCode } from '@/types/apiErrorCode'

/** Kept across visits (localStorage): clients come back weeks later to book again. */
const SESSION_KEY = 'linkcut.client-session'

function readSession(): ClientSession | null {
  try {
    const raw = localStorage.getItem(SESSION_KEY)
    return raw ? (JSON.parse(raw) as ClientSession) : null
  } catch {
    return null
  }
}

function writeSession(session: ClientSession | null): void {
  try {
    if (session) localStorage.setItem(SESSION_KEY, JSON.stringify(session))
    else localStorage.removeItem(SESSION_KEY)
  } catch {
    // Storage blocked: the session lasts while the page is open.
  }
}

/** The client's session, separate from the barber's (its own storage and its own token in the HTTP client). */
export const useClientStore = defineStore('client', () => {
  const apiErrors = useApiErrors()
  const session = ref<ClientSession | null>(readSession())
  let sessionExpiredListener: (() => void) | null = null
  /** One refresh at a time: two parallel refreshes would look like a reused (stolen) token. */
  let refreshing: Promise<boolean> | null = null

  /** The client's appointments at every barbershop (shared by "Meus horários" and "Barbearias"). */
  const appointments = ref<ClientAppointment[]>([])
  const appointmentsLoaded = ref(false)

  const client = computed(() => session.value?.client ?? null)
  const isSignedIn = computed(() => Boolean(session.value) && session.value!.refreshExpiresAt > Date.now())

  function setSession(next: ClientSession | null): void {
    session.value = next
    writeSession(next)
  }

  async function doRefresh(): Promise<boolean> {
    const current = session.value
    if (!current || current.refreshExpiresAt <= Date.now()) {
      setSession(null)
      return false
    }
    try {
      setSession(await clientApi.refreshSession(current.refreshToken))
      return true
    } catch (caught) {
      // Offline: keep the session to try again later; refused by the API: it is over.
      if (apiErrors.errorCode(caught) !== ApiErrorCode.NetworkError) setSession(null)
      return false
    }
  }

  function refreshAccess(): Promise<boolean> {
    refreshing ??= doRefresh().finally(() => (refreshing = null))
    return refreshing
  }

  async function ensureSession(): Promise<boolean> {
    return (Boolean(session.value) && session.value!.accessExpiresAt > Date.now()) || refreshAccess()
  }

  async function login(email: string, password: string): Promise<void> {
    setSession(await clientApi.login(email.trim(), password))
  }

  /** Completes the sign-up or sets a new password with the code from the e-mail. */
  async function setPassword(...args: Parameters<typeof clientApi.setPassword>): Promise<void> {
    setSession(await clientApi.setPassword(...args))
  }

  function forget(): void {
    setSession(null)
    appointments.value = []
    appointmentsLoaded.value = false
  }

  async function logout(): Promise<void> {
    const current = session.value
    try {
      if (current) await clientApi.logout(current.refreshToken)
    } catch {
      // Offline or already expired: the server tokens expire on their own.
    } finally {
      forget()
    }
  }

  async function loadAppointments(): Promise<void> {
    appointments.value = await clientApi.fetchAppointments()
    appointmentsLoaded.value = true
  }

  /** Cached for the session: switching tabs does not reload the list. */
  async function ensureAppointments(): Promise<void> {
    if (!appointmentsLoaded.value) await loadAppointments()
  }

  /** Cancels and reloads (the API is the source of truth for the status). */
  async function cancelAppointment(id: number): Promise<void> {
    await clientApi.cancelAppointment(id)
    await loadAppointments()
  }

  function keepProfile(profile: ClientProfile): void {
    if (session.value) setSession({ ...session.value, client: profile })
  }

  async function updateProfile(input: { name: string; phone: string }): Promise<void> {
    keepProfile(await clientApi.updateProfile(input))
  }

  async function setEmailsEnabled(enabled: boolean): Promise<void> {
    keepProfile(await clientApi.updateEmailPreference(enabled))
  }

  /** The other devices are signed out; this one keeps going with the new session. */
  async function changePassword(input: { currentPassword: string; password: string }): Promise<void> {
    setSession(await clientApi.changePassword(input))
  }

  /** LGPD: after the API confirms, nothing of the client stays in this browser. */
  async function deleteAccount(password: string): Promise<void> {
    await clientApi.deleteAccount(password)
    forget()
  }

  function onSessionExpired(listener: () => void): void {
    sessionExpiredListener = listener
  }

  setHttpAuthHandlers(
    {
      accessToken: () => session.value?.accessToken ?? null,
      refresh: refreshAccess,
      onSessionExpired: () => {
        forget()
        sessionExpiredListener?.()
      },
    },
    'client',
  )

  return {
    session,
    client,
    isSignedIn,
    ensureSession,
    login,
    setPassword,
    logout,
    onSessionExpired,
    appointments,
    appointmentsLoaded,
    loadAppointments,
    ensureAppointments,
    cancelAppointment,
    updateProfile,
    setEmailsEnabled,
    changePassword,
    deleteAccount,
  }
})
