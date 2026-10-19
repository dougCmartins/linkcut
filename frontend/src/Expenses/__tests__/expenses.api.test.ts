import { beforeEach, describe, expect, it } from 'vitest'
import { addFixedExpense, addOneOffExpense, fetchExpenses, removeExpense, restoreExpense, updateExpense } from '../expenses.api'

const NOW = new Date(2026, 9, 17, 15, 0)

describe('expenses api (mock)', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('starts with editable examples on the first visit', async () => {
    // Arrange & Act
    const expenses = await fetchExpenses(NOW)

    // Assert
    expect(expenses.fixed.map((expense) => expense.name)).toContain('Aluguel da cadeira')
    expect(expenses.oneOff.every((expense) => expense.date.startsWith('2026-10') && expense.date <= '2026-10-17')).toBe(true)
  })

  it('keeps added expenses after a reload', async () => {
    // Arrange
    await fetchExpenses(NOW)

    // Act
    const fixed = await addFixedExpense({ name: 'Software', amountInCents: 4_990, dueDay: 15 }, NOW)
    const oneOff = await addOneOffExpense({ description: 'Máquina nova', amountInCents: 45_000, date: '2026-10-16' }, NOW)

    // Assert
    const reloaded = await fetchExpenses(NOW)
    expect(reloaded.fixed).toContainEqual(fixed)
    expect(reloaded.oneOff).toContainEqual(oneOff)
  })

  it('removes an expense and restores it with the same id (undo)', async () => {
    // Arrange
    const [rent] = (await fetchExpenses(NOW)).fixed

    // Act
    await removeExpense(rent!.id, NOW)
    const afterRemove = await fetchExpenses(NOW)
    await restoreExpense(rent!, NOW)

    // Assert
    expect(afterRemove.fixed).not.toContainEqual(rent)
    expect((await fetchExpenses(NOW)).fixed).toContainEqual(rent)
  })

  it('edits an expense keeping its id, and the change survives a reload', async () => {
    // Arrange
    const { fixed, oneOff } = await fetchExpenses(NOW)
    const rent = fixed[0]!
    const edited = { ...rent, name: 'Aluguel da cadeira (novo)', amountInCents: 180_000 }

    // Act
    await updateExpense(edited, NOW)

    // Assert
    const reloaded = await fetchExpenses(NOW)
    expect(reloaded.fixed.find((expense) => expense.id === rent.id)).toEqual(edited)
    expect(reloaded.fixed).toHaveLength(fixed.length)
    expect(reloaded.oneOff).toEqual(oneOff)
  })
})
