const BRAZIL_COUNTRY_CODE = '55'

export function onlyDigits(value: string): string {
  return value.replace(/\D/g, '')
}

/** Brazilian phone with area code: 10 (landline) or 11 (mobile) digits. */
export function isValidBrazilianPhone(value: string): boolean {
  const digits = onlyDigits(value)
  return digits.length === 10 || digits.length === 11
}

/** Accepts a Brazilian phone with area code, with or without the 55 country code. */
export function isValidWhatsappNumber(value: string): boolean {
  const digits = onlyDigits(value)
  if (isValidBrazilianPhone(digits)) return true
  return digits.startsWith(BRAZIL_COUNTRY_CODE) && isValidBrazilianPhone(digits.slice(BRAZIL_COUNTRY_CODE.length))
}

/** Number in the format wa.me expects: digits only, always with the country code. */
export function toWhatsappNumber(value: string): string {
  const digits = onlyDigits(value)
  return isValidBrazilianPhone(digits) ? `${BRAZIL_COUNTRY_CODE}${digits}` : digits
}

export function buildWhatsappUrl(phone: string, message: string): string {
  const text = message.trim() ? `?text=${encodeURIComponent(message.trim())}` : ''
  return `https://wa.me/${toWhatsappNumber(phone)}${text}`
}
