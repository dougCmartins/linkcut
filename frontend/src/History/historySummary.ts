import type { CompletedService, HistorySummary } from './history.model'

export function summarize(records: CompletedService[]): HistorySummary {
  const totalInCents = records.reduce((sum, record) => sum + record.priceInCents, 0)
  const count = records.length
  return { totalInCents, count, averageTicketInCents: count > 0 ? Math.round(totalInCents / count) : 0 }
}

export interface Page<T> {
  items: T[]
  page: number
  totalPages: number
  /** 1-based position of the first and last item shown (0 when empty). */
  firstItem: number
  lastItem: number
}

/** Slices one page. Out-of-range pages are clamped to the last one. */
export function paginate<T>(items: T[], page: number, pageSize: number): Page<T> {
  const totalPages = Math.max(1, Math.ceil(items.length / pageSize))
  const current = Math.min(Math.max(1, page), totalPages)
  const start = (current - 1) * pageSize
  const pageItems = items.slice(start, start + pageSize)
  return {
    items: pageItems,
    page: current,
    totalPages,
    firstItem: pageItems.length ? start + 1 : 0,
    lastItem: start + pageItems.length,
  }
}

export interface DayGroup {
  date: string
  totalInCents: number
  records: CompletedService[]
}

/** Groups records (already sorted newest first) by day, keeping the order. */
export function groupByDay(records: CompletedService[]): DayGroup[] {
  const groups: DayGroup[] = []
  for (const record of records) {
    const last = groups.at(-1)
    if (last?.date === record.date) {
      last.records.push(record)
      last.totalInCents += record.priceInCents
    } else {
      groups.push({ date: record.date, totalInCents: record.priceInCents, records: [record] })
    }
  }
  return groups
}
