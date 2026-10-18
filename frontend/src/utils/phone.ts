/** Brazilian phone with area code: 10 (landline) or 11 (mobile) digits. */
export function isValidBrazilianPhone(value: string): boolean {
  const digits = value.replace(/\D/g, '')
  return digits.length === 10 || digits.length === 11
}

export function onlyDigits(value: string): string {
  return value.replace(/\D/g, '')
}
