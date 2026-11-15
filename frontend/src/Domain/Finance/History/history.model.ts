/** One finished appointment (what the barber did and charged). */
export interface CompletedService {
  id: number
  /** Local date "YYYY-MM-DD". */
  date: string
  /** Local time "HH:mm". */
  time: string
  clientName: string
  serviceName: string
  /** Price charged at the time (kept even if the service price changes later). */
  priceInCents: number
  /** Cost per procedure at the time (material, products). Zero = no cost registered. */
  costInCents: number
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

/** Totals of the whole period (not only of the page). */
export interface HistorySummary {
  totalInCents: number
  count: number
  averageTicketInCents: number
}

/** Some rows of the history, newest first. The totals come only with the first rows; `nextCursor` asks for more. */
export interface HistoryPage {
  items: CompletedService[]
  summary: HistorySummary | null
  nextCursor: string | null
}
