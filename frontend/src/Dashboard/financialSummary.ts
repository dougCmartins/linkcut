/** Healthy profit margin for the barber sector, in percent. */
export const HEALTHY_MARGIN_PERCENT = 25

export interface FinancialSummary {
  revenueInCents: number
  expensesInCents: number
  profitInCents: number
  marginPercent: number
  isBelowHealthyMargin: boolean
}

export function computeFinancialSummary(revenueInCents: number, expensesInCents: number): FinancialSummary {
  const profitInCents = revenueInCents - expensesInCents
  const marginPercent = revenueInCents > 0 ? (profitInCents / revenueInCents) * 100 : 0

  return {
    revenueInCents,
    expensesInCents,
    profitInCents,
    marginPercent,
    isBelowHealthyMargin: marginPercent < HEALTHY_MARGIN_PERCENT,
  }
}
