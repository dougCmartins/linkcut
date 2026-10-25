import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, within } from '@testing-library/vue'
import DashboardView from '../DashboardView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { fetchMonthToDateFinances } from '@/Domain/Finance/monthToDate.api'
import { formatBRL } from '@/utils/currency'
import { createTestSettings, installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { emptyAddress } from '@/Domain/Settings/settings.model'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { createFinanceRoutes, type RevenueRow } from '@/Domain/Finance/__tests__/fakeFinanceApi'
import { createScheduleRoutes, requestRow, type RequestRow } from '@/Domain/Schedule/__tests__/fakeScheduleApi'

const TODAY = '2026-10-17'

/** Settings, Finance and Schedule fakes together; concluding moves the fake revenue, like the API. */
function installFakeApis(requests?: RequestRow[]) {
  const finance = createFinanceRoutes({ today: TODAY })
  const schedule = createScheduleRoutes({
    today: TODAY,
    requests,
    onComplete: (row, price, cost) =>
      finance.state.history.push({
        id: 10_000 + row.id,
        date: row.date,
        time: row.starts_at,
        client_name: row.client_name,
        service_name: row.service_name,
        price_in_cents: price,
        cost_in_cents: cost,
      } satisfies RevenueRow),
    onReopen: (row) => {
      finance.state.history = finance.state.history.filter((entry) => entry.id !== 10_000 + row.id)
    },
  })
  installFakeSettingsApi(undefined, { ...finance.routes, ...schedule.routes })
  return { finance, schedule }
}

describe('DashboardView', () => {
  beforeEach(() => {
    installFakeApis()
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date(2026, 9, 17, 18, 0))
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('links to the public page of the barbershop in the same tab (a new one closes in-app browsers)', async () => {
    // Arrange & Act
    renderWithPlugins(DashboardView)

    // Assert
    const link = await screen.findByRole('link', { name: /Ver minha página/ })
    await vi.waitFor(() => expect(link.getAttribute('href')).toBe('/rafael'))
    expect(link.getAttribute('target')).toBeNull()
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
    const finances = await fetchMonthToDateFinances()

    // Act
    renderWithPlugins(DashboardView)

    // Assert
    const expectedLabel = `Faturamento do mês até hoje: ${formatBRL(finances.revenueInCents)}. Ver histórico`
    const link = await screen.findByRole('link', { name: (name) => name === expectedLabel })
    expect(link.getAttribute('href')).toBe('/painel/historico')
  })

  it('shows an empty agenda message when nothing is confirmed for today', async () => {
    // Arrange
    installFakeApis([])

    // Act
    renderWithPlugins(DashboardView)

    // Assert
    expect(await screen.findByText(/Nenhum atendimento para hoje/)).toBeTruthy()
  })

  it('shows placeholders, not "nothing here", while the agenda loads', () => {
    // Arrange & Act
    renderWithPlugins(DashboardView)

    // Assert
    expect(screen.queryByText(/Nenhum atendimento para hoje/)).toBeNull()
    expect(screen.queryByText(/Nenhuma solicitação/)).toBeNull()
  })

  it('an accepted request for another day goes to "Próximos atendimentos"', async () => {
    // Arrange
    installFakeApis([requestRow({ id: 7, client_name: 'Caio Rocha', date: '2026-10-19', starts_at: '09:00' })])
    renderWithPlugins(DashboardView)
    const pending = await screen.findByRole('heading', { name: 'Caio Rocha' })

    // Act
    await fireEvent.click(within(pending.closest('article') as HTMLElement).getByRole('button', { name: /Aceitar/ }))

    // Assert
    const upcoming = await screen.findByRole('region', { name: 'Próximos atendimentos' })
    expect(within(upcoming).getByText('Caio Rocha')).toBeTruthy()
    expect(within(upcoming).getByText(/19\/10/)).toBeTruthy()
  })

  it('accept → agenda → conclude adds the price to the revenue, and undo takes it back', async () => {
    // Arrange
    const { schedule } = installFakeApis([requestRow({ id: 5, client_name: 'Ana Souza', date: TODAY, starts_at: '09:00' })])
    const revenueBefore = (await fetchMonthToDateFinances()).revenueInCents
    const revenueLink = (cents: number) => (name: string) => name === `Faturamento do mês até hoje: ${formatBRL(cents)}. Ver histórico`
    renderWithPlugins(DashboardView)
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
    expect(schedule.requests[0]?.status).toBe('confirmed')
    expect(screen.getByText('0 de 1 concluídos')).toBeTruthy()
  })

  it('lists the pending requests from the API with the service price', async () => {
    // Arrange & Act (App.vue loads the barber's settings on dashboard pages)
    renderWithPlugins(DashboardView)
    await useSettingsStore().ensureLoaded()

    // Assert
    const card = (await screen.findByText('Bruno Lima')).closest('article') as HTMLElement
    expect(within(card).getByText('Barba')).toBeTruthy()
    expect(within(card).getByText(/R\$\s40,00/)).toBeTruthy()
    expect(screen.getByText('Pedro Martins')).toBeTruthy()
  })

  it('asks the barber for the address when the barbershop has none', async () => {
    // Arrange
    const settings = createTestSettings()
    settings.profile.address = emptyAddress()
    installFakeSettingsApi(settings, { ...createFinanceRoutes({ today: TODAY }).routes, ...createScheduleRoutes({ today: TODAY }).routes })

    // Act
    renderWithPlugins(DashboardView)
    await useSettingsStore().ensureLoaded()

    // Assert
    const link = await screen.findByRole('link', { name: 'Cadastrar endereço' })
    expect(link.getAttribute('href')).toBe('/painel/configuracoes?aba=link')
    expect(screen.getByText(/Cadastre o endereço da barbearia/)).toBeTruthy()
  })

  it('shows the API message when a request was already answered elsewhere', async () => {
    // Arrange
    const { schedule } = installFakeApis()
    renderWithPlugins(DashboardView)
    const card = (await screen.findByText('Bruno Lima')).closest('article') as HTMLElement
    schedule.requests[0]!.status = 'rejected'

    // Act
    await fireEvent.click(within(card).getByRole('button', { name: 'Aceitar' }))

    // Assert
    expect(await screen.findByText('Este pedido já foi atualizado. Atualize a página.')).toBeTruthy()
    await vi.waitFor(() => expect(screen.queryByText('Bruno Lima')).toBeNull())
  })

  it('the margin tooltip explains the calculation with the month numbers', async () => {
    // Arrange
    const { revenueInCents, expenses } = await fetchMonthToDateFinances()
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
    const { expenses } = await fetchMonthToDateFinances()

    // Act
    renderWithPlugins(DashboardView)

    // Assert
    const expectedLabel = `Despesas do mês até hoje: ${formatBRL(expenses.totalInCents)}. Ver e cadastrar despesas`
    const link = await screen.findByRole('link', { name: (name) => name === expectedLabel })
    expect(link.getAttribute('href')).toBe('/painel/despesas')
  })
})
