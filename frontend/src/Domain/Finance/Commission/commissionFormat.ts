/** Percent values travel as tenths of a percent (50% = 500); people type and read plain percents ("50", "7,5"). */

export function tenthsToInput(tenths: number): string {
  return String(tenths / 10).replace('.', ',')
}

export function formatPercent(tenths: number): string {
  return `${tenthsToInput(tenths)}%`
}

/** "50" or "7,5" -> tenths (500, 75). Empty, invalid or out of 0…max percent -> null. */
export function parsePercentInput(text: string, maxPercent = 100): number | null {
  const compact = text.replace(/\s|%/g, '').replace(',', '.')
  if (!compact) return null
  const value = Number(compact)
  if (!Number.isFinite(value) || value < 0 || value > maxPercent) return null
  return Math.round(value * 10)
}

/** Month start and end (ISO dates) of the month that contains `date`. */
export function monthBounds(date: Date): { from: string; to: string } {
  const iso = (d: Date) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  return {
    from: iso(new Date(date.getFullYear(), date.getMonth(), 1)),
    to: iso(new Date(date.getFullYear(), date.getMonth() + 1, 0)),
  }
}
