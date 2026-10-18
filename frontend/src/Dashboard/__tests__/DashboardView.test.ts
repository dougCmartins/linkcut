import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import DashboardView from '../DashboardView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { fetchMonthToDateFinances } from '@/History/history.api'
import { formatBRL } from '@/utils/currency'

describe('DashboardView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date(2026, 9, 17, 18, 0))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it("shows today's date and that the numbers are month to date", () => {
    // Arrange & Act
    renderWithPlugins(DashboardView)

    // Assert
    expect(screen.getByText('Sábado, 17 de outubro')).toBeTruthy()
    expect(screen.getByText('Resumo de outubro')).toBeTruthy()
    expect(screen.getByText('Mês até hoje')).toBeTruthy()
  })

  it('shows the month-to-date revenue from the history and links to it', async () => {
    // Arrange
    const finances = await fetchMonthToDateFinances(new Date())

    // Act
    renderWithPlugins(DashboardView)

    // Assert
    const expectedLabel = `Faturamento do mês até hoje: ${formatBRL(finances.revenueInCents)}. Ver histórico`
    const link = await screen.findByRole('link', { name: (name) => name === expectedLabel })
    expect(link.getAttribute('href')).toBe('/painel/historico')
  })
})
