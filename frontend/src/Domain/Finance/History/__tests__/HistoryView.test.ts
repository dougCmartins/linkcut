import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import HistoryView from '../HistoryView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { installFakeFinanceApi } from '../../__tests__/fakeFinanceApi'

async function renderHistory() {
  const rendered = renderWithPlugins(HistoryView)
  await screen.findAllByText(/atend\. ·/)
  return rendered
}

describe('HistoryView', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date(2026, 9, 17, 18, 0)) // Saturday 17 Oct 2026, end of the day
    installFakeFinanceApi()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('opens on the current month with its total and the day groups', async () => {
    // Arrange & Act
    await renderHistory()

    // Assert
    expect(screen.getByRole('radio', { name: 'Mês', checked: true })).toBeTruthy()
    expect(screen.getByText(/Período: 1.*17 de out\. de 2026/)).toBeTruthy()
    expect(screen.getByText('Sábado, 17 de outubro')).toBeTruthy()
  })

  it('brings the next rows at the end of the list, without pages', async () => {
    // Arrange
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Ano' }))
    const total = Number((await screen.findByText(/^50 de \d+$/)).textContent?.split(' de ')[1])

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Carregar mais' }))

    // Assert
    expect(await screen.findByText(`100 de ${total}`)).toBeTruthy()
    expect(screen.queryByText(/Página/)).toBeNull()
  })

  it('validates the custom dates', async () => {
    // Arrange
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Personalizado' }))

    // Act
    await fireEvent.update(screen.getByLabelText('De'), '2026-10-10')
    await fireEvent.update(screen.getByLabelText('Até'), '2026-10-01')

    // Assert
    expect(screen.getByText('A data final precisa ser igual ou depois da inicial.')).toBeTruthy()
    expect(screen.getByText('Ajuste as datas para ver o histórico.')).toBeTruthy()

    // Act: more than one year
    await fireEvent.update(screen.getByLabelText('De'), '2025-01-01')
    await fireEvent.update(screen.getByLabelText('Até'), '2026-10-17')

    // Assert
    expect(screen.getByText('Escolha até 1 ano por vez.')).toBeTruthy()
  })

  it('shows the totals of the whole period from the API, not of the page', async () => {
    // Arrange & Act
    await renderHistory()

    // Assert: 17 days × 3 appointments (R$ 170,00 per day)
    expect(screen.getByText(/^50 de 51$/)).toBeTruthy()
    expect(screen.getByText(/R\$\s2\.890,00/)).toBeTruthy()
    expect(screen.getByText('51')).toBeTruthy()
  })

  it('asks for the CSV by e-mail and says where it goes, without waiting for it', async () => {
    // Arrange
    await renderHistory()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Receber CSV por e-mail' }))

    // Assert
    expect(await screen.findByText(/Ela chega em instantes em ra\*\*\*\*@linkcut\.app/)).toBeTruthy()
    expect(screen.getByRole('button', { name: 'Receber CSV por e-mail' }).hasAttribute('disabled')).toBe(true)
  })
})
