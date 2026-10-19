const brlFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

/** Formats an amount in cents as Brazilian Real (e.g. 5000 -> "R$ 50,00"). */
export function formatBRL(amountInCents: number): string {
  return brlFormatter.format(amountInCents / 100)
}

/** Money typed by the user: "50,00", "50.5" or "1.500,00" -> cents. Empty or invalid -> null. */
export function parseMoneyInput(text: string): number | null {
  const compact = text.replace(/\s|R\$/g, '')
  if (!compact) return null
  // "1.500,00": the dot is a thousands separator when a comma follows.
  const normalized = compact.includes(',') ? compact.replace(/\./g, '').replace(',', '.') : compact
  const value = Number(normalized)
  return Number.isFinite(value) ? Math.round(value * 100) : null
}

/** Cents -> text for a money input ("50,00"); zero shows as empty. */
export function formatMoneyInput(amountInCents: number): string {
  return amountInCents > 0 ? (amountInCents / 100).toFixed(2).replace('.', ',') : ''
}
