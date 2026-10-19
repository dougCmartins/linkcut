import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, within } from '@testing-library/vue'
import DashboardView from '../DashboardView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { fetchMonthToDateFinances } from '@/Dashboard/dashboard.api'
import { formatBRL } from '@/utils/currency'
import { useBookingStore } from '@/Booking/booking.store'

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

  it('shows an empty agenda message when nothing is confirmed for today', () => {
    // Arrange & Act
    renderWithPlugins(DashboardView)

    // Assert
    expect(screen.getByText(/Nenhum atendimento confirmado para hoje/)).toBeTruthy()
  })

  it('accept → agenda → conclude adds the price to the revenue, and undo takes it back', async () => {
    // Arrange
    const revenueBefore = (await fetchMonthToDateFinances(new Date())).revenueInCents
    const revenueLink = (cents: number) => (name: string) =>
      name === `Faturamento do mês até hoje: ${formatBRL(cents)}. Ver histórico`
    renderWithPlugins(DashboardView)
    const bookingStore = useBookingStore()
    const request = bookingStore.submitRequest({
      clientName: 'Ana Souza',
      clientWhatsapp: '11912345678',
      serviceId: 'haircut',
      date: '2026-10-17',
      time: '09:00',
    })
    await screen.findByRole('link', { name: revenueLink(revenueBefore) })

    // Act
    const pendingCard = (await screen.findByText('Ana Souza')).closest('article') as HTMLElement
    await fireEvent.click(within(pendingCard).getByRole('button', { name: 'Aceitar' }))
    await fireEvent.click(await screen.findByRole('button', { name: 'Concluir atendimento de Ana Souza às 09:00' }))

    // Assert
    await screen.findByRole('link', { name: revenueLink(revenueBefore + 5000) })
    expect(screen.getByText('1 de 1 concluídos')).toBeTruthy()
    expect(screen.getByText(/Atendimento concluído · R\$\s50,00 no faturamento/)).toBeTruthy()

    // Act (undo)
    await fireEvent.click(screen.getByRole('button', { name: 'Desfazer' }))

    // Assert (undo)
    await screen.findByRole('link', { name: revenueLink(revenueBefore) })
    expect(bookingStore.requests.find((item) => item.id === request.id)?.status).toBe('confirmed')
    expect(screen.getByText('0 de 1 concluídos')).toBeTruthy()
  })

  it('the margin tooltip explains the calculation with the month numbers', async () => {
    // Arrange
    const { revenueInCents, expenses } = await fetchMonthToDateFinances(new Date())
    const profit = revenueInCents - expenses.totalInCents
    const margin = `${((profit / revenueInCents) * 100).toFixed(1).replace('.', ',')}%`
    renderWithPlugins(DashboardView)
    const button = await screen.findByRole('button', { name: 'Como a margem é calculada' })
    await screen.findByText(margin)

    // Act
    await fireEvent.click(button)

    // Assert
    const tooltip = screen.getByRole('tooltip')
    const text = tooltip.textContent!.replace(/\s/g, ' ')
    expect(text).toContain('Margem = Lucro ÷ Faturamento × 100')
    expect(text).toContain(`${formatBRL(profit)} ÷ ${formatBRL(revenueInCents)} × 100 = ${margin}`.replace(/\s/g, ' '))
    expect(text).toContain(`Custos dos atendimentos${formatBRL(expenses.serviceCostsInCents)}`.replace(/\s/g, ' '))
    expect(text).toContain('Acima de 25% é saudável')
  })

  it('links the expenses card to the expenses screen', async () => {
    // Arrange
    const { expenses } = await fetchMonthToDateFinances(new Date())

    // Act
    renderWithPlugins(DashboardView)

    // Assert
    const expectedLabel = `Despesas do mês até hoje: ${formatBRL(expenses.totalInCents)}. Ver e cadastrar despesas`
    const link = await screen.findByRole('link', { name: (name) => name === expectedLabel })
    expect(link.getAttribute('href')).toBe('/painel/despesas')
  })
})
