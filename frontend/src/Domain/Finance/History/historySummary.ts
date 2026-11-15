import type { CompletedService } from './history.model'

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
