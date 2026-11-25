import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import { renderWithPlugins } from '@/utils/testRender'
import { envelope, type FakeHandler } from '@/utils/testFetch'
import { installFakeSettingsApi, createTestSettings } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import CommissionView from '../CommissionView.vue'

function startSession(role: 'owner' | 'barber', userId: number): void {
  useAuthStore().startSession({
    accessToken: 'a',
    refreshToken: 'r',
    accessExpiresAt: Date.now() + 60_000,
    refreshExpiresAt: Date.now() + 120_000,
    user: {
      id: userId,
      tenantId: 3,
      name: role,
      email: `${role}@example.com`,
      phoneMasked: null,
      role,
      twoFactorChannel: null,
      availableChannels: [],
    },
  })
}

const staffBody = (seeOwnCommission: boolean) => [
  {
    id: 1,
    tenant_id: 3,
    user_id: 7,
    name: 'Owner',
    avatar: null,
    active: true,
    pending_invite: false,
    permissions: { see_prices: true, see_own_commission: true, see_unit_agenda: true },
  },
  {
    id: 2,
    tenant_id: 3,
    user_id: 8,
    name: 'Barber',
    avatar: null,
    active: true,
    pending_invite: false,
    permissions: { see_prices: false, see_own_commission: seeOwnCommission, see_unit_agenda: false },
  },
]

const closing = (reopenedAt: string | null) => ({
  id: 5,
  from: '2026-11-01',
  to: '2026-11-30',
  closed_at: '2026-11-30T20:00:00Z',
  closed_by: 7,
  reopened_at: reopenedAt,
})

const emptyReport = {
  from: '2026-11-01',
  to: '2026-11-30',
  total_amount_in_cents: 4600,
  entries: [
    {
      id: 1,
      booking_request_id: 11,
      staff_id: 2,
      date: '2026-11-10',
      base_in_cents: 10000,
      percent: 500,
      adjustments: [{ name: 'Taxa', stage: 'after', kind: 'percent', applies_to: 'price', amount_in_cents: 400 }],
      amount_in_cents: 4600,
      closing_id: null,
    },
  ],
}

function install(seeOwnCommission = false, extra: Record<string, FakeHandler> = {}) {
  return installFakeSettingsApi(createTestSettings(), {
    'GET /team/staff': () => ({ status: 200, body: staffBody(seeOwnCommission) }),
    'GET /finance/commission/rules': () => ({ status: 200, body: [] }),
    'GET /finance/commission/adjustments': () => ({ status: 200, body: [] }),
    'GET /finance/commission/closings': () => ({ status: 200, body: [] }),
    'GET /finance/commission/report': () => ({ status: 200, body: emptyReport }),
    ...extra,
  })
}

describe('CommissionView', () => {
  it('shows the owner the three tabs and the simulated amount', async () => {
    // Arrange
    install(false, {
      'POST /finance/commission/simulate': () => ({
        status: 200,
        body: { base_in_cents: 10000, percent: 500, amount_in_cents: 4600, adjustments: [] },
      }),
    })
    renderWithPlugins(CommissionView, {}, () => startSession('owner', 7))

    // Act
    await waitFor(() => expect(screen.getByRole('tab', { name: /Regras/ })).toBeTruthy())
    await userEvent.click(screen.getByRole('button', { name: 'Simular' }))

    // Assert
    expect(screen.getByRole('tab', { name: /Ajustes/ })).toBeTruthy()
    expect(screen.getByRole('tab', { name: /Relatório/ })).toBeTruthy()
    await waitFor(() => expect(screen.getByTestId('simulation-result').textContent).toMatch(/R\$\s46,00/))
  })

  it('closes the period and reopens it', async () => {
    // Arrange
    let closings = [] as ReturnType<typeof closing>[]
    const { calls } = install(false, {
      'GET /finance/commission/closings': () => ({ status: 200, body: closings }),
      'POST /finance/commission/closings': () => {
        closings = [closing(null)]
        return { status: 201, body: closings[0] }
      },
      'POST /finance/commission/closings/:id/reopen': () => ({ status: 200, body: closing('2026-12-01T10:00:00Z') }),
    })
    renderWithPlugins(CommissionView, {}, () => startSession('owner', 7))
    await waitFor(() => expect(screen.getByRole('tab', { name: /Relatório/ })).toBeTruthy())
    await userEvent.click(screen.getByRole('tab', { name: /Relatório/ }))

    // Act
    await userEvent.click(await screen.findByRole('button', { name: 'Fechar período' }))
    await userEvent.click(await screen.findByRole('button', { name: /Reabrir o fechamento/ }))

    // Assert
    await waitFor(() => expect(screen.getByText('Reaberto')).toBeTruthy())
    expect(calls.some((call) => call.method === 'POST' && call.path === '/finance/commission/closings/5/reopen')).toBe(true)
  })

  it('shows an API error in the envelope when the period is closed', async () => {
    // Arrange
    install(false, {
      'POST /finance/commission/closings': () => envelope('COMMISSION_PERIOD_CLOSED', 409),
    })
    renderWithPlugins(CommissionView, {}, () => startSession('owner', 7))
    await waitFor(() => expect(screen.getByRole('tab', { name: /Relatório/ })).toBeTruthy())
    await userEvent.click(screen.getByRole('tab', { name: /Relatório/ }))

    // Act
    await userEvent.click(await screen.findByRole('button', { name: 'Fechar período' }))

    // Assert
    await waitFor(() => expect(screen.getByRole('alert').textContent).toContain('período fechado'))
  })

  it('does not request or show commission to a barber without the permission', async () => {
    // Arrange
    const { calls } = install(false)
    renderWithPlugins(CommissionView, {}, () => startSession('barber', 8))

    // Act
    await waitFor(() => expect(screen.getByText(/ainda não liberou/)).toBeTruthy())

    // Assert
    expect(calls.some((call) => call.path.startsWith('/finance/commission'))).toBe(false)
    expect(screen.queryByRole('tab')).toBeNull()
  })

  it('shows only the report to a barber with the permission', async () => {
    // Arrange
    const { calls } = install(true)
    renderWithPlugins(CommissionView, {}, () => startSession('barber', 8))

    // Act
    await waitFor(() => expect(screen.getByTestId('report-total').textContent).toMatch(/R\$\s46,00/))

    // Assert
    expect(screen.queryByRole('tab')).toBeNull()
    expect(screen.queryByRole('button', { name: 'Fechar período' })).toBeNull()
    expect(calls.some((call) => call.path.startsWith('/finance/commission/rules'))).toBe(false)
    expect(calls.some((call) => call.path.startsWith('/finance/commission/report'))).toBe(true)
  })
})
