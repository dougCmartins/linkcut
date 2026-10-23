/**
 * The visitor's privacy choice (LGPD), kept in this browser. "Necessary" storage (sessions, theme, error
 * reports without personal data) needs no consent; "performance" (Sentry tracing and replay) does.
 * Bump CONSENT_VERSION when the notice changes: everyone is asked again.
 */
export const CONSENT_VERSION = 1
export const CONSENT_KEY = 'linkcut.consent'

export interface ConsentChoice {
  version: number
  performance: boolean
  /** ISO date of the choice (proof of consent). */
  decidedAt: string
}

export function readConsent(): ConsentChoice | null {
  try {
    const raw = localStorage.getItem(CONSENT_KEY)
    const choice = raw ? (JSON.parse(raw) as ConsentChoice) : null
    return choice?.version === CONSENT_VERSION ? choice : null
  } catch {
    return null
  }
}

export function writeConsent(performance: boolean, now: Date = new Date()): ConsentChoice {
  const choice: ConsentChoice = { version: CONSENT_VERSION, performance, decidedAt: now.toISOString() }
  try {
    localStorage.setItem(CONSENT_KEY, JSON.stringify(choice))
  } catch {
    // Storage blocked: the choice lasts while the page is open.
  }
  return choice
}
