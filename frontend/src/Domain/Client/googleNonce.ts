/**
 * The Google sign-in is tied to the browser that started it: the link sends a random nonce, this browser keeps it,
 * and only that nonce trades the one-time code at the end. A code link opened anywhere else does nothing.
 */
const KEY = 'linkcut.google-nonce'

export function newGoogleNonce(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(24))
  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
}

export function keepGoogleNonce(nonce: string): void {
  try {
    localStorage.setItem(KEY, nonce)
  } catch {
    // Storage blocked: the exchange will fail and the client can use the password instead.
  }
}

/** Reads the nonce once; it is not reused for another sign-in. */
export function takeGoogleNonce(): string {
  try {
    const nonce = localStorage.getItem(KEY) ?? ''
    localStorage.removeItem(KEY)
    return nonce
  } catch {
    return ''
  }
}
