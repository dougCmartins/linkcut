const brlFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

/** Formats an amount in cents as Brazilian Real (e.g. 5000 -> "R$ 50,00"). */
export function formatBRL(amountInCents: number): string {
  return brlFormatter.format(amountInCents / 100)
}
