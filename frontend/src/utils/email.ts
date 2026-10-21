/**
 * A practical e-mail check for forms (the API validates for real). Small on purpose: the booking modal is on every
 * public page, and a validation library there would make each first visit heavier.
 */
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/

export function isValidEmail(value: string): boolean {
  return EMAIL_PATTERN.test(value.trim())
}
