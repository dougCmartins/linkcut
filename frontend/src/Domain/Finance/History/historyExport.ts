import type { DateRange } from './history.model'

/** Same name the API sends, so the browser saves the file with it. */
export function historyFileName(range: DateRange): string {
  return `historico-linkcut_${range.from}_a_${range.to}.csv`
}
