import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  addFixedExpense,
  addOneOffExpense,
  fetchExpenses,
  removeFixedExpense,
  restoreFixedExpense,
  updateOneOffExpense,
} from '../expenses.api'
import { installFakeFinanceApi } from '../../__tests__/fakeFinanceApi'

describe('expenses api', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('reads the fixed and the one-off expenses in the SPA format', async () => {
    // Arrange
    installFakeFinanceApi()

    // Act
    const list = await fetchExpenses()

    // Assert
    expect(list.fixed[0]).toEqual({ id: 1, name: 'Aluguel da cadeira', amountInCents: 150_000, dueDay: 5 })
    expect(list.oneOff[0]).toEqual({ id: 1, description: 'Lâminas e pomadas', amountInCents: 21_000, date: '2026-10-02' })
  })

  it('sends new and edited expenses in the API format', async () => {
    // Arrange
    const { calls } = installFakeFinanceApi()

    // Act
    const fixed = await addFixedExpense({ name: 'Software', amountInCents: 4990, dueDay: 15 })
    await addOneOffExpense({ description: 'Toalhas', amountInCents: 8500, date: '2026-10-12' })
    await updateOneOffExpense(2, { description: 'Toalhas novas', amountInCents: 9000, date: '2026-10-13' })

    // Assert
    expect(fixed).toEqual({ id: 100, name: 'Software', amountInCents: 4990, dueDay: 15 })
    expect(calls.map((call) => [call.method, call.path, call.body])).toEqual([
      ['POST', '/finance/fixed-expenses', { name: 'Software', amount_in_cents: 4990, due_day: 15 }],
      ['POST', '/finance/one-off-expenses', { description: 'Toalhas', amount_in_cents: 8500, date: '2026-10-12' }],
      ['PUT', '/finance/one-off-expenses/2', { description: 'Toalhas novas', amount_in_cents: 9000, date: '2026-10-13' }],
    ])
  })

  it('removes and restores an expense (undo)', async () => {
    // Arrange
    installFakeFinanceApi()

    // Act
    await removeFixedExpense(2)
    const afterRemove = await fetchExpenses()
    const restored = await restoreFixedExpense(2)

    // Assert
    expect(afterRemove.fixed.map((expense) => expense.id)).toEqual([1, 3])
    expect(restored.name).toBe('Internet')
    expect((await fetchExpenses()).fixed).toHaveLength(3)
  })

  it('rejects with the API error code when the expense does not exist', async () => {
    // Arrange
    installFakeFinanceApi()

    // Act
    const removing = removeFixedExpense(999)

    // Assert
    await expect(removing).rejects.toMatchObject({ code: 'EXPENSE_NOT_FOUND', status: 404 })
  })
})
