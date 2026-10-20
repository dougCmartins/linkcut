import { z } from 'zod'

/** Limits shared by the schemas and the form inputs. */
export const EXPENSE_LIMITS = {
  nameMax: 40,
  /** R$ 100.000,00 */
  maxAmountInCents: 10_000_000,
} as const

/** Error messages are i18n keys under `expenses.errors`. */
const amount = z
  .number({ error: 'amount' })
  .int('amount')
  .positive('amount')
  .max(EXPENSE_LIMITS.maxAmountInCents, 'amountTooHigh')

const label = z.string().trim().min(1, 'nameRequired').max(EXPENSE_LIMITS.nameMax, 'nameTooLong')

export const fixedExpenseSchema = z.object({
  name: label,
  amountInCents: amount,
  dueDay: z.number({ error: 'dueDay' }).int('dueDay').min(1, 'dueDay').max(31, 'dueDay'),
})

/** `today` ("YYYY-MM-DD"): a one-off expense is something already paid, never a future date. */
export function oneOffExpenseSchema(today: string) {
  return z.object({
    description: label,
    amountInCents: amount,
    date: z.iso.date('dateRequired').refine((date) => date <= today, 'dateInFuture'),
  })
}

/** First error of each field, e.g. { amountInCents: 'amount' }. */
export function fieldErrors(error: z.ZodError): Record<string, string> {
  const errors: Record<string, string> = {}
  for (const issue of error.issues) {
    const field = String(issue.path[0] ?? '')
    errors[field] ??= issue.message
  }
  return errors
}
