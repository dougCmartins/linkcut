import { describe, expect, it } from 'vitest'
import { fieldErrors, fixedExpenseSchema, oneOffExpenseSchema } from '../expenses.schemas'

describe('fixedExpenseSchema', () => {
  it('accepts a valid fixed expense and trims the name', () => {
    // Arrange & Act
    const result = fixedExpenseSchema.safeParse({ name: '  Aluguel  ', amountInCents: 150_000, dueDay: 5 })

    // Assert
    expect(result.success && result.data).toEqual({ name: 'Aluguel', amountInCents: 150_000, dueDay: 5 })
  })

  it('reports each invalid field with its i18n key', () => {
    // Arrange & Act
    const result = fixedExpenseSchema.safeParse({ name: ' ', amountInCents: 0, dueDay: 32 })

    // Assert
    expect(result.success).toBe(false)
    expect(fieldErrors(result.error!)).toEqual({ name: 'nameRequired', amountInCents: 'amount', dueDay: 'dueDay' })
  })

  it('rejects an unreadable amount and a value above the limit', () => {
    // Arrange & Act
    const unreadable = fixedExpenseSchema.safeParse({ name: 'X', amountInCents: Number.NaN, dueDay: 1 })
    const tooHigh = fixedExpenseSchema.safeParse({ name: 'X', amountInCents: 10_000_001, dueDay: 1 })

    // Assert
    expect(fieldErrors(unreadable.error!).amountInCents).toBe('amount')
    expect(fieldErrors(tooHigh.error!).amountInCents).toBe('amountTooHigh')
  })
})

describe('oneOffExpenseSchema', () => {
  const schema = oneOffExpenseSchema('2026-10-17')

  it('accepts an expense paid today', () => {
    // Arrange & Act
    const result = schema.safeParse({ description: 'Lâminas', amountInCents: 2_100, date: '2026-10-17' })

    // Assert
    expect(result.success).toBe(true)
  })

  it('rejects a future or missing date', () => {
    // Arrange & Act
    const future = schema.safeParse({ description: 'Lâminas', amountInCents: 2_100, date: '2026-10-18' })
    const missing = schema.safeParse({ description: 'Lâminas', amountInCents: 2_100, date: '' })

    // Assert
    expect(fieldErrors(future.error!).date).toBe('dateInFuture')
    expect(fieldErrors(missing.error!).date).toBe('dateRequired')
  })
})
