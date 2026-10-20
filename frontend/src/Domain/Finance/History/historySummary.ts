import type { CompletedService } from './history.model'

/** 1-based position of the first and last item of a page (0 when empty). */
export function pageBounds(page: number, perPage: number, itemCount: number): { firstItem: number; lastItem: number } {
  if (itemCount === 0) return { firstItem: 0, lastItem: 0 }
  const firstItem = (page - 1) * perPage + 1
  return { firstItem, lastItem: firstItem + itemCount - 1 }
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
