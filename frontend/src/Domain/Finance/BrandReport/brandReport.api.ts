import { http } from '@/http/client'
import type { BrandRange, BrandReport, BrandTotals } from './brandReport.model'

/** Brand report API (snake_case on the wire, camelCase in the SPA). Owner only: the others get 403. */

interface TotalsBody {
  revenue_in_cents: number
  count: number
  average_ticket_in_cents: number
  commission_in_cents: number
}

interface ReportBody {
  from: string
  to: string
  total: TotalsBody
  units: { unit_id: number; name: string; slug: string; is_main: boolean; totals: TotalsBody }[]
}

const toTotals = (body: TotalsBody): BrandTotals => ({
  revenueInCents: body.revenue_in_cents,
  count: body.count,
  averageTicketInCents: body.average_ticket_in_cents,
  commissionInCents: body.commission_in_cents,
})

export async function fetchBrandReport(range: BrandRange): Promise<BrandReport> {
  const params = new URLSearchParams({ from: range.from, to: range.to })
  const { data } = await http<ReportBody>(`/finance/brand/report?${params.toString()}`)
  return {
    from: data.from,
    to: data.to,
    total: toTotals(data.total),
    units: data.units.map((unit) => ({
      unitId: unit.unit_id,
      name: unit.name,
      slug: unit.slug,
      isMain: unit.is_main,
      totals: toTotals(unit.totals),
    })),
  }
}

/** "Receber CSV por e-mail": the API queues the report and answers with the (masked) e-mail it goes to. */
export async function requestBrandReportCsv(range: BrandRange): Promise<{ email: string }> {
  const { data } = await http<{ email: string }>('/finance/brand/report', {
    method: 'POST',
    body: { from: range.from, to: range.to },
  })
  return data
}
