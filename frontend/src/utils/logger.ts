/**
 * Structured security log (one JSON line per event), ready to ship to a log tool later.
 * Never pass secrets here: no passwords, codes or tokens.
 */
export function logSecurityEvent(event: string, data: Record<string, string | number | boolean | null> = {}): void {
  if (import.meta.env.MODE === 'test') return
  console.warn(JSON.stringify({ level: 'warn', category: 'security', event, at: new Date().toISOString(), ...data }))
}
