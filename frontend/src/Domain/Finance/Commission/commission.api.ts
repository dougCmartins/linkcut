import { http } from '@/http/client'
import type {
  AdjustmentAppliesTo,
  AdjustmentKind,
  AdjustmentStage,
  CommissionAdjustment,
  CommissionAdjustmentInput,
  CommissionClosing,
  CommissionEntry,
  CommissionLine,
  CommissionReport,
  CommissionRule,
  CommissionSimulation,
  NewCommissionRule,
  ReportRange,
  SimulationInput,
} from './commission.model'

/** Commission API (snake_case on the wire, camelCase in the SPA). */

interface RuleBody {
  id: number
  service_id: number
  staff_id: number | null
  percent: number
  starts_on: string
}

interface AdjustmentBody {
  id: number
  staff_id: number | null
  name: string
  stage: AdjustmentStage
  kind: AdjustmentKind
  applies_to: AdjustmentAppliesTo
  value: number
  active: boolean
  starts_on: string
  system_key: string | null
}

interface LineBody {
  name: string
  stage: AdjustmentStage
  kind: AdjustmentKind
  applies_to: AdjustmentAppliesTo
  amount_in_cents: number
}

interface SimulationBody {
  base_in_cents: number
  percent: number
  amount_in_cents: number
  adjustments: LineBody[]
}

interface EntryBody {
  id: number
  booking_request_id: number
  staff_id: number
  date: string
  base_in_cents: number
  percent: number
  adjustments: LineBody[]
  amount_in_cents: number
  closing_id: number | null
}

interface ReportBody {
  from: string
  to: string
  total_amount_in_cents: number
  entries: EntryBody[]
}

interface ClosingBody {
  id: number
  from: string
  to: string
  closed_at: string
  closed_by: number
  reopened_at: string | null
}

const toRule = (body: RuleBody): CommissionRule => ({
  id: body.id,
  serviceId: body.service_id,
  staffId: body.staff_id,
  percent: body.percent,
  startsOn: body.starts_on,
})

const toAdjustment = (body: AdjustmentBody): CommissionAdjustment => ({
  id: body.id,
  staffId: body.staff_id,
  name: body.name,
  stage: body.stage,
  kind: body.kind,
  appliesTo: body.applies_to,
  value: body.value,
  active: body.active,
  startsOn: body.starts_on,
  systemKey: body.system_key,
})

const adjustmentBody = (input: CommissionAdjustmentInput) => ({
  staff_id: input.staffId,
  name: input.name,
  stage: input.stage,
  kind: input.kind,
  applies_to: input.appliesTo,
  value: input.value,
  active: input.active,
  starts_on: input.startsOn,
})

const toLine = (body: LineBody): CommissionLine => ({
  name: body.name,
  stage: body.stage,
  kind: body.kind,
  appliesTo: body.applies_to,
  amountInCents: body.amount_in_cents,
})

const toEntry = (body: EntryBody): CommissionEntry => ({
  id: body.id,
  bookingRequestId: body.booking_request_id,
  staffId: body.staff_id,
  date: body.date,
  baseInCents: body.base_in_cents,
  percent: body.percent,
  adjustments: body.adjustments.map(toLine),
  amountInCents: body.amount_in_cents,
  closingId: body.closing_id,
})

const toClosing = (body: ClosingBody): CommissionClosing => ({
  id: body.id,
  from: body.from,
  to: body.to,
  closedAt: body.closed_at,
  closedBy: body.closed_by,
  reopenedAt: body.reopened_at,
})

export async function fetchRules(): Promise<CommissionRule[]> {
  const { data } = await http<RuleBody[]>('/finance/commission/rules')
  return data.map(toRule)
}

export async function createRule(rule: NewCommissionRule): Promise<CommissionRule> {
  const { data } = await http<RuleBody>('/finance/commission/rules', {
    method: 'POST',
    body: { service_id: rule.serviceId, staff_id: rule.staffId, percent: rule.percent, starts_on: rule.startsOn },
  })
  return toRule(data)
}

export async function deleteRule(id: number): Promise<void> {
  await http<null>(`/finance/commission/rules/${id}`, { method: 'DELETE' })
}

export async function fetchAdjustments(): Promise<CommissionAdjustment[]> {
  const { data } = await http<AdjustmentBody[]>('/finance/commission/adjustments')
  return data.map(toAdjustment)
}

export async function createAdjustment(input: CommissionAdjustmentInput): Promise<CommissionAdjustment> {
  const { data } = await http<AdjustmentBody>('/finance/commission/adjustments', { method: 'POST', body: adjustmentBody(input) })
  return toAdjustment(data)
}

export async function updateAdjustment(id: number, input: CommissionAdjustmentInput): Promise<CommissionAdjustment> {
  const { data } = await http<AdjustmentBody>(`/finance/commission/adjustments/${id}`, { method: 'PUT', body: adjustmentBody(input) })
  return toAdjustment(data)
}

export async function deleteAdjustment(id: number): Promise<void> {
  await http<null>(`/finance/commission/adjustments/${id}`, { method: 'DELETE' })
}

export async function simulate(input: SimulationInput): Promise<CommissionSimulation> {
  const { data } = await http<SimulationBody>('/finance/commission/simulate', {
    method: 'POST',
    body: {
      price_in_cents: input.priceInCents,
      percent: input.percent,
      cost_in_cents: input.costInCents,
      staff_id: input.staffId,
      use_saved_adjustments: input.useSavedAdjustments,
    },
  })
  return {
    baseInCents: data.base_in_cents,
    percent: data.percent,
    amountInCents: data.amount_in_cents,
    adjustments: data.adjustments.map(toLine),
  }
}

export async function fetchClosings(): Promise<CommissionClosing[]> {
  const { data } = await http<ClosingBody[]>('/finance/commission/closings')
  return data.map(toClosing)
}

export async function closePeriod(range: ReportRange): Promise<CommissionClosing> {
  const { data } = await http<ClosingBody>('/finance/commission/closings', { method: 'POST', body: { from: range.from, to: range.to } })
  return toClosing(data)
}

export async function reopenClosing(id: number): Promise<CommissionClosing> {
  const { data } = await http<ClosingBody>(`/finance/commission/closings/${id}/reopen`, { method: 'POST' })
  return toClosing(data)
}

export async function fetchReport(range: ReportRange, staffId: number | null = null): Promise<CommissionReport> {
  const params = new URLSearchParams({ from: range.from, to: range.to })
  if (staffId !== null) params.set('staff_id', String(staffId))
  const { data } = await http<ReportBody>(`/finance/commission/report?${params.toString()}`)
  return { from: data.from, to: data.to, totalInCents: data.total_amount_in_cents, entries: data.entries.map(toEntry) }
}

/** "Exportar CSV": the API queues the report and e-mails it; answers with the (masked) e-mail. */
export async function requestReportCsv(range: ReportRange, staffId: number | null = null): Promise<{ email: string }> {
  const body: Record<string, unknown> = { from: range.from, to: range.to }
  if (staffId !== null) body.staff_id = staffId
  const { data } = await http<{ email: string }>('/finance/commission/report', { method: 'POST', body })
  return data
}
