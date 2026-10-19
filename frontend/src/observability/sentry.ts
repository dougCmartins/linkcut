import * as Sentry from '@sentry/vue'
import type { App } from 'vue'
import type { Router } from 'vue-router'

/**
 * Sentry for the SPA (off when VITE_SENTRY_DSN is empty).
 * Every event carries tenant_id and user_id, so an error can be traced to the barbershop
 * it happened to; traces are joined to the API ones through the request headers.
 * Session Replay hides all text and media (customer data never leaves the browser).
 */
let isEnabled = false

export function initSentry(app: App, router: Router): void {
  const dsn = import.meta.env.VITE_SENTRY_DSN
  if (!dsn) return

  const apiUrl = import.meta.env.VITE_API_URL ?? ''
  Sentry.init({
    app,
    dsn,
    environment: import.meta.env.MODE,
    sendDefaultPii: false,
    integrations: [
      Sentry.browserTracingIntegration({ router }),
      Sentry.replayIntegration({ maskAllText: true, blockAllMedia: true }),
    ],
    tracesSampleRate: Number(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? 0.2),
    tracePropagationTargets: apiUrl ? [apiUrl] : [],
    replaysSessionSampleRate: 0,
    replaysOnErrorSampleRate: 1,
  })
  isEnabled = true
}

/** Tags the current barber (after login) or clears it (logout). Only ids, never e-mail or name. */
export function setObservabilityUser(user: { id: number; tenantId: number } | null): void {
  if (!isEnabled) return
  Sentry.setUser(user ? { id: String(user.id) } : null)
  Sentry.setTag('tenant_id', user ? String(user.tenantId) : undefined)
}
