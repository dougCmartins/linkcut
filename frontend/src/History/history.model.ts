/** One finished appointment (what the barber did and charged). */
export interface CompletedService {
  id: string
  /** Local date "YYYY-MM-DD". */
  date: string
  /** Local time "HH:mm". */
  time: string
  clientName: string
  serviceName: string
  /** Price charged at the time (kept even if the service price changes later). */
  priceInCents: number
}

/** Inclusive range of local dates, "YYYY-MM-DD". */
export interface DateRange {
  from: string
  to: string
}

export const HistoryPeriod = {
  Week: 'week',
  Month: 'month',
  Year: 'year',
  Custom: 'custom',
} as const

export type HistoryPeriod = (typeof HistoryPeriod)[keyof typeof HistoryPeriod]

export const PAGE_SIZE_OPTIONS = [50, 100, 200] as const

export type PageSize = (typeof PAGE_SIZE_OPTIONS)[number]

export interface HistorySummary {
  totalInCents: number
  count: number
  averageTicketInCents: number
}

export interface MonthFinances {
  revenueInCents: number
  expensesInCents: number
}
