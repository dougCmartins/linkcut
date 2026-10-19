import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import HistoryView from '../HistoryView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import * as csv from '@/utils/csv'

async function renderHistory() {
  const rendered = renderWithPlugins(HistoryView)
  await screen.findAllByText(/atend\. ·/)
  return rendered
}

function countLabel(): string {
  return screen.getByText(/^\d+–\d+ de \d+$/).textContent ?? ''
}

describe('HistoryView', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date(2026, 9, 17, 18, 0)) // Saturday 17 Oct 2026, end of the day
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.restoreAllMocks()
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
    expect(countLabel()).toMatch(/^1–100 de \d+$/)
  })

  it('moves to the next page', async () => {
    // Arrange
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Ano' }))
    await screen.findByText(/^1–50 de \d+$/)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: /Próxima/ }))

    // Assert
    expect(countLabel()).toMatch(/^51–100 de \d+$/)
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

  it('exports every record of the period as CSV', async () => {
    // Arrange
    const download = vi.spyOn(csv, 'downloadTextFile').mockImplementation(() => undefined)
    await renderHistory()
    await fireEvent.click(screen.getByRole('radio', { name: 'Ano' }))
    const total = Number((await screen.findByText(/^1–50 de \d+$/)).textContent?.split(' de ')[1])

    // Act
    await fireEvent.click(screen.getByRole('button', { name: /Exportar CSV/ }))

    // Assert
    const [fileName, content] = download.mock.calls[0] ?? []
    expect(fileName).toBe('historico-linkcut_2026-01-01_a_2026-10-17.csv')
    expect((content ?? '').trim().split('\r\n')).toHaveLength(total + 1)
  })
})
