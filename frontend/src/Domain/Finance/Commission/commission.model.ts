/** Commission domain (percent values are tenths of a percent: 50% = 500; money is in cents). */

export type AdjustmentStage = 'before' | 'after'
export type AdjustmentKind = 'percent' | 'fixed'
export type AdjustmentAppliesTo = 'price' | 'commission' | 'appointment'

/** Key of the built-in "service cost" adjustment (can only be switched on or off). */
export const SERVICE_COST_SYSTEM_KEY = 'service_cost'

export interface CommissionRule {
  id: number
  serviceId: number
  /** Null = the rule of the service for everyone; set = the exception of one barber. */
  staffId: number | null
  percent: number
  startsOn: string
}

export interface NewCommissionRule {
  serviceId: number
  staffId: number | null
  percent: number
  startsOn: string
}

export interface CommissionAdjustment {
  id: number
  staffId: number | null
  name: string
  stage: AdjustmentStage
  kind: AdjustmentKind
  appliesTo: AdjustmentAppliesTo
  value: number
  active: boolean
  startsOn: string
  systemKey: string | null
}

export type CommissionAdjustmentInput = Omit<CommissionAdjustment, 'id' | 'systemKey'>

export interface CommissionLine {
  name: string
  stage: AdjustmentStage
  kind: AdjustmentKind
  appliesTo: AdjustmentAppliesTo
  amountInCents: number
}

export interface CommissionSimulation {
  baseInCents: number
  percent: number
  amountInCents: number
  adjustments: CommissionLine[]
}

export interface SimulationInput {
  priceInCents: number
  percent: number
  costInCents: number
  staffId: number
  useSavedAdjustments: boolean
}

export interface CommissionEntry {
  id: number
  bookingRequestId: number
  staffId: number
  date: string
  baseInCents: number
  percent: number
  adjustments: CommissionLine[]
  amountInCents: number
  closingId: number | null
}

export interface CommissionReport {
  from: string
  to: string
  totalInCents: number
  entries: CommissionEntry[]
}

export interface CommissionClosing {
  id: number
  from: string
  to: string
  closedAt: string
  closedBy: number
  reopenedAt: string | null
}

export interface ReportRange {
  from: string
  to: string
}
