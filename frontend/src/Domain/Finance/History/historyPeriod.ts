import { z } from 'zod'
import { HistoryPeriod, type DateRange } from './history.model'
import { toIsoDate } from '@/utils/date'

const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/
const MONDAY = 1

/** Monday of the week that contains `date` (weeks start on Monday in Brazil's business calendar). */
export function startOfWeek(date: Date): Date {
  const start = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const daysSinceMonday = (start.getDay() - MONDAY + 7) % 7
  start.setDate(start.getDate() - daysSinceMonday)
  return start
}

/** Current month from day 1 until today — the dashboard numbers use this range. */
export function monthToDate(today: Date): DateRange {
  return { from: toIsoDate(new Date(today.getFullYear(), today.getMonth(), 1)), to: toIsoDate(today) }
}

/** Range for each preset, always "until today". Custom uses the dates the barber typed. */
export function periodRange(period: HistoryPeriod, today: Date, custom: DateRange): DateRange {
  const to = toIsoDate(today)
  switch (period) {
    case HistoryPeriod.Week:
      return { from: toIsoDate(startOfWeek(today)), to }
    case HistoryPeriod.Month:
      return monthToDate(today)
    case HistoryPeriod.Year:
      return { from: toIsoDate(new Date(today.getFullYear(), 0, 1)), to }
    case HistoryPeriod.Custom:
      return custom
  }
}

/** Values are i18n keys under `history.errors`. */
export const customRangeSchema = z
  .object({
    from: z.string().regex(ISO_DATE, 'fromRequired'),
    to: z.string().regex(ISO_DATE, 'toRequired'),
  })
  .refine((range) => range.from <= range.to, { message: 'rangeOrder', path: ['to'] })

/** First error key of a custom range, or null when it can be used. */
export function customRangeError(range: DateRange): string | null {
  const result = customRangeSchema.safeParse(range)
  return result.success ? null : (result.error.issues[0]?.message ?? 'rangeOrder')
}
