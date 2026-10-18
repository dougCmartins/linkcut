import type { CompletedService, DateRange } from './history.model'
import { fromIsoDate } from '@/utils/date'
import { toCsv } from '@/utils/csv'

const dateFormatter = new Intl.DateTimeFormat('pt-BR')

export interface HistoryCsvHeaders {
  date: string
  time: string
  client: string
  service: string
  value: string
}

/** Value as "50,00" (number with decimal comma) so spreadsheets can sum the column. */
function formatAmount(priceInCents: number): string {
  return (priceInCents / 100).toFixed(2).replace('.', ',')
}

export function buildHistoryCsv(records: CompletedService[], headers: HistoryCsvHeaders): string {
  return toCsv(
    [headers.date, headers.time, headers.client, headers.service, headers.value],
    records.map((record) => [
      dateFormatter.format(fromIsoDate(record.date)),
      record.time,
      record.clientName,
      record.serviceName,
      formatAmount(record.priceInCents),
    ]),
  )
}

export function historyFileName(range: DateRange): string {
  return `historico-linkcut_${range.from}_a_${range.to}.csv`
}
