import * as Sentry from '@sentry/vue'
import type { App } from 'vue'
import type { Router } from 'vue-router'
import { readConsent } from '@/Legal/consent'

/**
 * Sentry for the SPA (off when VITE_SENTRY_DSN is empty).
 * - Errors: always on (legitimate interest: keeping the service safe). No personal data: sendDefaultPii is off and
 *   only ids are tagged (tenant_id, user_id).
 * - Performance (tracing) and the replay of the moments before an error: only with the visitor's consent
 *   ("Desempenho" in the privacy notice). Turned on or off at any time by setPerformanceMonitoring().
 * Session Replay hides all text and media (customer data never leaves the browser).
 */
let isEnabled = false
let isPerformanceOn = false
let tracingAdded = false
let currentRouter: Router | null = null

function tracesSampleRate(): number {
  return Number(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? 0.2)
}

export function initSentry(app: App, router: Router): void {
  const dsn = import.meta.env.VITE_SENTRY_DSN
  if (!dsn) return

  const apiUrl = import.meta.env.VITE_API_URL ?? ''
  currentRouter = router
  Sentry.init({
    app,
    dsn,
    environment: import.meta.env.MODE,
    sendDefaultPii: false,
    // Tracing and replay are added only after consent (setPerformanceMonitoring).
    integrations: [],
    tracesSampler: () => (isPerformanceOn ? tracesSampleRate() : 0),
    tracePropagationTargets: apiUrl ? [apiUrl] : [],
    replaysSessionSampleRate: 0,
    replaysOnErrorSampleRate: 1,
  })
  isEnabled = true
  setPerformanceMonitoring(readConsent()?.performance ?? false)
}

/** Follows the visitor's choice: on adds tracing and the error replay; off stops them. */
export function setPerformanceMonitoring(allowed: boolean): void {
  if (!isEnabled) return
  isPerformanceOn = allowed
  if (allowed) {
    if (!tracingAdded && currentRouter) {
      Sentry.addIntegration(Sentry.browserTracingIntegration({ router: currentRouter }))
      tracingAdded = true
    }
    if (!Sentry.getReplay()) Sentry.addIntegration(Sentry.replayIntegration({ maskAllText: true, blockAllMedia: true }))
    else Sentry.getReplay()?.startBuffering()
    return
  }
  void Sentry.getReplay()?.stop()
}

/** Tags the current barber (after login) or clears it (logout). Only ids, never e-mail or name. */
export function setObservabilityUser(user: { id: number; tenantId: number } | null): void {
  if (!isEnabled) return
  Sentry.setUser(user ? { id: String(user.id) } : null)
  Sentry.setTag('tenant_id', user ? String(user.tenantId) : undefined)
}
