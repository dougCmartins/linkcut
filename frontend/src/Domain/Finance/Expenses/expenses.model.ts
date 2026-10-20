/** A cost that repeats every month (chair rent, internet, DAS/MEI). */
export interface FixedExpense {
  id: number
  name: string
  amountInCents: number
  /** Day of the month it is paid (1–31). In shorter months it falls on the last day. */
  dueDay: number
}

/** A cost paid once (supplies, a new clipper). */
export interface OneOffExpense {
  id: number
  description: string
  amountInCents: number
  /** Local date "YYYY-MM-DD". */
  date: string
}

export interface ExpenseList {
  fixed: FixedExpense[]
  oneOff: OneOffExpense[]
}

export type FixedExpenseInput = Omit<FixedExpense, 'id'>
export type OneOffExpenseInput = Omit<OneOffExpense, 'id'>

/** What went out of the cash in a period, by origin. */
export interface ExpenseBreakdown {
  /** Fixed expenses already due. */
  fixedInCents: number
  oneOffInCents: number
  /** Cost per procedure of the appointments concluded in the period. */
  serviceCostsInCents: number
  totalInCents: number
}
