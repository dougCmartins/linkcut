import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import { renderWithPlugins } from '@/utils/testRender'
import { envelope, installFakeApi } from '@/utils/testFetch'
import BrandReportView from '../BrandReportView.vue'

const totals = (revenue: number, count: number, commission: number) => ({
  revenue_in_cents: revenue,
  count,
  average_ticket_in_cents: count ? Math.round(revenue / count) : 0,
  commission_in_cents: commission,
})

const body = {
  from: '2026-10-01',
  to: '2026-10-31',
  total: totals(12000, 2, 6000),
  units: [
    { unit_id: 10, name: 'Matriz', slug: 'marca', is_main: true, totals: totals(5000, 1, 2500) },
    { unit_id: 11, name: 'Centro', slug: 'marca-centro', is_main: false, totals: totals(7000, 1, 3500) },
  ],
}

describe('BrandReportView', () => {
  it('shows the brand total and the breakdown by unit', async () => {
    // Arrange
    installFakeApi({ 'GET /finance/brand/report': () => ({ status: 200, body }) })

    // Act
    renderWithPlugins(BrandReportView)

    // Assert
    await waitFor(() => expect(screen.getByTestId('brand-total-revenue').textContent).toMatch(/R\$\s120,00/))
    expect(screen.getByText(/Matriz \(matriz\)/)).toBeTruthy()
    expect(screen.getByText('Centro')).toBeTruthy()
  })

  it('asks the CSV by e-mail', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /finance/brand/report': () => ({ status: 200, body }),
      'POST /finance/brand/report': () => ({ status: 202, body: { email: 'o***@example.com' } }),
    })
    renderWithPlugins(BrandReportView)
    await waitFor(() => expect(screen.getByTestId('brand-total-revenue')).toBeTruthy())

    // Act
    await userEvent.click(screen.getByRole('button', { name: 'Receber CSV por e-mail' }))

    // Assert
    await waitFor(() => expect(screen.getByRole('status').textContent).toContain('o***@example.com'))
    expect(calls.some((call) => call.method === 'POST' && call.path === '/finance/brand/report')).toBe(true)
  })

  it('shows the error of the envelope when the user is not the brand owner', async () => {
    // Arrange
    installFakeApi({ 'GET /finance/brand/report': () => envelope('BRAND_REPORT_FORBIDDEN', 403) })

    // Act
    renderWithPlugins(BrandReportView)

    // Assert
    await waitFor(() => expect(screen.getByRole('alert').textContent).toContain('Só a dona da marca'))
    expect(screen.queryByTestId('brand-total-revenue')).toBeNull()
  })
})
