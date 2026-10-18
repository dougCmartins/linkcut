/** Returns `count` consecutive days starting at `from`, each set to midnight local time. */
export function nextDays(count: number, from: Date = new Date()): Date[] {
  const start = new Date(from.getFullYear(), from.getMonth(), from.getDate())
  return Array.from({ length: count }, (_, index) => {
    const day = new Date(start)
    day.setDate(start.getDate() + index)
    return day
  })
}

/** Local calendar date as ISO "YYYY-MM-DD" (no timezone shift). */
export function toIsoDate(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

/** Parses "YYYY-MM-DD" as a local date. */
export function fromIsoDate(isoDate: string): Date {
  const [year = 0, month = 1, day = 1] = isoDate.split('-').map(Number)
  return new Date(year, month - 1, day)
}

export function formatWeekdayShort(date: Date, locale = 'pt-BR'): string {
  return new Intl.DateTimeFormat(locale, { weekday: 'short' }).format(date).replace('.', '')
}

/** Weekday name for 0 (Sunday) … 6 (Saturday), e.g. "segunda-feira". */
export function formatWeekdayLong(weekday: number, locale = 'pt-BR'): string {
  const reference = new Date(2026, 0, 4 + weekday) // 2026-01-04 is a Sunday
  return new Intl.DateTimeFormat(locale, { weekday: 'long' }).format(reference)
}

export function formatDayMonth(date: Date, locale = 'pt-BR'): string {
  return new Intl.DateTimeFormat(locale, { day: '2-digit', month: 'short' }).format(date).replace('.', '')
}
