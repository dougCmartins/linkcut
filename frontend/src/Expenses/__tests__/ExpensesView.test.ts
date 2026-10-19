import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, within } from '@testing-library/vue'
import ExpensesView from '../ExpensesView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { formatBRL } from '@/utils/currency'
import { fetchMonthToDateFinances } from '@/Dashboard/dashboard.api'

const parseTotal = (text: string) => Number(text.replace(/[^\d,]/g, '').replace(',', '.'))

const totalText = async () => {
  const label = await screen.findByText('Despesas do mês até hoje')
  return label.parentElement!.querySelector('p:nth-of-type(2)')!.textContent!
}

describe('ExpensesView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date(2026, 9, 17, 18, 0))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows the month total and the fixed expenses with their due status', async () => {
    // Arrange
    const { expenses } = await fetchMonthToDateFinances(new Date())

    // Act
    renderWithPlugins(ExpensesView)

    // Assert
    await screen.findByText('Aluguel da cadeira')
    await vi.waitFor(async () =>
      expect((await totalText()).replace(/\s/g, ' ')).toBe(formatBRL(expenses.totalInCents).replace(/\s/g, ' ')),
    )
    expect(screen.getByText('Dia 5 · já entrou')).toBeTruthy()
    expect(screen.getByText('Entra no dia 20')).toBeTruthy()
  })

  it('adds a fixed expense already due and the total goes up', async () => {
    // Arrange
    renderWithPlugins(ExpensesView)
    await screen.findByText('Aluguel da cadeira')
    await vi.waitFor(async () => expect(await totalText()).not.toBe('—'))
    const before = await totalText()
    await fireEvent.click(screen.getByRole('button', { name: /Adicionar despesa fixa/ }))

    // Act
    await fireEvent.update(screen.getByLabelText('Nome'), 'Software de agenda')
    await fireEvent.update(screen.getByLabelText('Valor (R$)'), '49,90')
    await fireEvent.update(screen.getByLabelText('Dia do vencimento'), '15')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    await screen.findByText('Software de agenda')
    await vi.waitFor(async () => expect(await totalText()).not.toBe(before))
    expect(screen.getByText('Dia 15 · já entrou')).toBeTruthy()
  })

  it('shows the errors and saves nothing when the form is invalid', async () => {
    // Arrange
    renderWithPlugins(ExpensesView)
    await screen.findByText('Aluguel da cadeira')
    await fireEvent.click(screen.getByRole('button', { name: /Adicionar despesa avulsa/ }))

    // Act
    await fireEvent.update(screen.getByLabelText('Valor (R$)'), '0')
    await fireEvent.update(screen.getByLabelText('Data'), '2026-10-30')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    expect(screen.getByText('Campo obrigatório.')).toBeTruthy()
    expect(screen.getByText('Informe um valor maior que zero.')).toBeTruthy()
    expect(screen.getByText('Lance só despesas já pagas (até hoje).')).toBeTruthy()
  })

  it('removes an expense and "Desfazer" brings it back', async () => {
    // Arrange
    renderWithPlugins(ExpensesView)
    await screen.findByText('Internet')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Remover Internet' }))
    const toast = await screen.findByRole('status')
    const removed = screen.queryByText('Internet') === null
    await fireEvent.click(within(toast).getByRole('button', { name: 'Desfazer' }))

    // Assert
    expect(removed).toBe(true)
    expect(await screen.findByText('Internet')).toBeTruthy()
  })

  it('edits the name and value of a fixed expense and the total follows', async () => {
    // Arrange
    renderWithPlugins(ExpensesView)
    await screen.findByText('Aluguel da cadeira')
    await vi.waitFor(async () => expect(await totalText()).not.toBe('—'))
    const before = parseTotal(await totalText())
    await fireEvent.click(screen.getByRole('button', { name: 'Editar Aluguel da cadeira' }))
    expect((screen.getByLabelText('Valor (R$)') as HTMLInputElement).value).toBe('1500,00')

    // Act
    await fireEvent.update(screen.getByLabelText('Nome'), 'Aluguel da cadeira 2027')
    await fireEvent.update(screen.getByLabelText('Valor (R$)'), '1.800,00')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    await screen.findByText('Aluguel da cadeira 2027')
    await vi.waitFor(async () => expect(parseTotal(await totalText())).toBe(before + 300))
    expect(screen.queryByRole('button', { name: 'Salvar' })).toBeNull()
  })

  it('keeps the old values when the edit is cancelled or invalid', async () => {
    // Arrange
    renderWithPlugins(ExpensesView)
    await screen.findByText('Internet')
    await fireEvent.click(screen.getByRole('button', { name: 'Editar Internet' }))

    // Act
    await fireEvent.update(screen.getByLabelText('Valor (R$)'), '0')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))
    const errorShown = screen.queryByText('Informe um valor maior que zero.') !== null
    await fireEvent.click(screen.getByRole('button', { name: 'Cancelar' }))

    // Assert
    expect(errorShown).toBe(true)
    expect(screen.getByText('Internet')).toBeTruthy()
    expect(screen.getByText(/R\$\s120,00/)).toBeTruthy()
  })

  it('edits a one-off expense', async () => {
    // Arrange
    renderWithPlugins(ExpensesView)
    await screen.findByText('Lâminas e pomadas')
    await fireEvent.click(screen.getByRole('button', { name: 'Editar Lâminas e pomadas' }))

    // Act
    await fireEvent.update(screen.getByLabelText('Descrição'), 'Lâminas, pomadas e talco')
    await fireEvent.update(screen.getByLabelText('Valor (R$)'), '250,00')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    expect(await screen.findByText('Lâminas, pomadas e talco')).toBeTruthy()
    expect(screen.getByText(/R\$\s250,00/)).toBeTruthy()
  })
})
