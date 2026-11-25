import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import { renderWithPlugins } from '@/utils/testRender'
import { installFakeApi } from '@/utils/testFetch'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import TeamView from '../TeamView.vue'

function installTeamApi(count: number) {
  const members = Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    tenant_id: 3,
    user_id: i === 0 ? 7 : null,
    name: i === 0 ? 'Owner' : `Barber ${i}`,
    avatar: null,
    active: true,
    pending_invite: i > 0,
    permissions: { see_prices: false, see_own_commission: false, see_unit_agenda: false },
  }))
  return installFakeApi({
    'GET /team/staff': () => ({ status: 200, body: members }),
    'POST /team/staff': ({ body }) => ({
      status: 201,
      body: {
        id: members.length + 1,
        tenant_id: 3,
        user_id: null,
        name: (body as { name: string }).name,
        avatar: null,
        active: true,
        pending_invite: true,
        permissions: { see_prices: false, see_own_commission: false, see_unit_agenda: false },
      },
    }),
  })
}

describe('TeamView', () => {
  it('owner can open invite form and send an invite', async () => {
    // Arrange
    installTeamApi(1)
    renderWithPlugins(TeamView, {}, () => {
      useAuthStore().startSession({
        accessToken: 'a',
        refreshToken: 'r',
        accessExpiresAt: Date.now() + 60_000,
        refreshExpiresAt: Date.now() + 120_000,
        user: {
          id: 7,
          tenantId: 3,
          name: 'Owner',
          email: 'owner@example.com',
          phoneMasked: null,
          role: 'owner',
          twoFactorChannel: null,
          availableChannels: [],
        },
      })
    })

    // Act
    await waitFor(() => expect(screen.getByText('Owner')).toBeTruthy())
    await userEvent.click(screen.getByRole('button', { name: /Adicionar barbeiro/i }))
    await userEvent.type(screen.getByLabelText(/Nome/i), 'João')
    await userEvent.type(screen.getByLabelText(/E-mail/i), 'joao@example.com')
    await userEvent.click(screen.getByRole('button', { name: /Enviar convite/i }))

    // Assert
    await waitFor(() => expect(screen.getByText('João')).toBeTruthy())
  })
})
