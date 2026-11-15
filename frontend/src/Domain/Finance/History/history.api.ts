import type { CompletedService, DateRange, HistoryPage } from './history.model'
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
  summary: { total_in_cents: number; count: number; average_ticket_in_cents: number } | null
  next_cursor: string | null
}

const query = (params: Record<string, string>) => new URLSearchParams(params).toString()

/**
 * Finished appointments inside the range (inclusive), newest first, a slice at a time. Without `cursor`: the first
 * rows and the totals of the period; with the `nextCursor` of the previous answer: the rows that follow.
 */
export async function fetchHistoryPage(range: DateRange, cursor: string | null = null): Promise<HistoryPage> {
  const params: Record<string, string> = { from: range.from, to: range.to }
  if (cursor) params.cursor = cursor
  const { data } = await http<HistoryPageBody>(`/finance/history?${query(params)}`)
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
    summary: data.summary
      ? {
          totalInCents: data.summary.total_in_cents,
          count: data.summary.count,
          averageTicketInCents: data.summary.average_ticket_in_cents,
        }
      : null,
    nextCursor: data.next_cursor,
  }
}

/**
 * "Exportar CSV", like a bank statement: the API queues the report and e-mails the CSV of the range. Answers at once
 * with the (masked) e-mail it goes to.
 */
export async function requestHistoryReport(range: DateRange): Promise<{ email: string }> {
  const { data } = await http<{ email: string }>('/finance/history/report', { method: 'POST', body: { from: range.from, to: range.to } })
  return data
}
