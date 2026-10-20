import type { CompletedService, DateRange, HistoryPage, PageSize } from './history.model'
import { http } from '@/http/client'

/** History API (snake_case on the wire, camelCase in the SPA). */

interface HistoryPageBody {
  items: {
    id: number
    date: string
    time: string
    client_name: string
    service_name: string
    price_in_cents: number
    cost_in_cents: number
  }[]
  summary: { total_in_cents: number; count: number; average_ticket_in_cents: number }
  page: number
  per_page: number
  last_page: number
}

const query = (params: Record<string, string | number>) =>
  new URLSearchParams(Object.entries(params).map(([key, value]) => [key, String(value)])).toString()

/** One page of the finished appointments inside the range (inclusive), newest first. */
export async function fetchHistoryPage(range: DateRange, page: number, perPage: PageSize): Promise<HistoryPage> {
  const { data } = await http<HistoryPageBody>(
    `/finance/history?${query({ from: range.from, to: range.to, page, per_page: perPage })}`,
  )
  return {
    items: data.items.map(
      (item): CompletedService => ({
        id: item.id,
        date: item.date,
        time: item.time,
        clientName: item.client_name,
        serviceName: item.service_name,
        priceInCents: item.price_in_cents,
        costInCents: item.cost_in_cents,
      }),
    ),
    summary: {
      totalInCents: data.summary.total_in_cents,
      count: data.summary.count,
      averageTicketInCents: data.summary.average_ticket_in_cents,
    },
    page: data.page,
    perPage: data.per_page,
    lastPage: data.last_page,
  }
}

/** CSV of the whole range, built by the API (";" separator, BOM, protected against formulas). */
export async function downloadHistoryCsv(range: DateRange): Promise<Blob> {
  const { data } = await http<Blob>(`/finance/history/export?${query({ from: range.from, to: range.to })}`, { responseType: 'blob' })
  return data
}
