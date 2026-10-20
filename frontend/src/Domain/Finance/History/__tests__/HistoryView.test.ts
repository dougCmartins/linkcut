import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import HistoryView from '../HistoryView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import * as download from '@/utils/download'
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

  it('shows 50 items per page and lets the barber pick 100', async () => {
    // Arrange
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Ano' }))
    await screen.findByText(/^1–50 de \d+$/)

    // Act
    await fireEvent.click(screen.getByRole('radio', { name: '100' }))

    // Assert
    expect(await screen.findByText(/^1–100 de \d+$/)).toBeTruthy()
  })

  it('moves to the next page', async () => {
    // Arrange
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Ano' }))
    await screen.findByText(/^1–50 de \d+$/)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: /Próxima/ }))

    // Assert
    expect(await screen.findByText(/^51–100 de \d+$/)).toBeTruthy()
    expect(screen.getByText(/Página 2 de \d+/)).toBeTruthy()
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
  })

  it('shows the totals of the whole period from the API, not of the page', async () => {
    // Arrange & Act
    await renderHistory()

    // Assert: 17 days × 3 appointments (R$ 170,00 per day)
    expect(screen.getByText(/^1–50 de 51$/)).toBeTruthy()
    expect(screen.getByText(/R\$\s2\.890,00/)).toBeTruthy()
    expect(screen.getByText('51')).toBeTruthy()
  })

  it('exports every record of the period as CSV', async () => {
    // Arrange
    const save = vi.spyOn(download, 'downloadBlob').mockImplementation(() => undefined)
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Ano' }))
    const total = Number((await screen.findByText(/^1–50 de \d+$/)).textContent?.split(' de ')[1])

    // Act
    await fireEvent.click(screen.getByRole('button', { name: /Exportar CSV/ }))

    // Assert
    await vi.waitFor(() => expect(save).toHaveBeenCalled())
    const [fileName, content] = save.mock.calls[0] ?? []
    expect(fileName).toBe('historico-linkcut_2026-01-01_a_2026-10-17.csv')
    expect((await content!.text()).trim().split('\r\n')).toHaveLength(total + 1)
  })
})
