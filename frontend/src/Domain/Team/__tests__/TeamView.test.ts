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

  it('each barber has an "Agenda" link for the owner, and none for a barber', async () => {
    // Arrange
    installTeamApi(2)
    const session = (role: 'owner' | 'barber') => ({
      accessToken: 'a',
      refreshToken: 'r',
      accessExpiresAt: Date.now() + 60_000,
      refreshExpiresAt: Date.now() + 120_000,
      user: { id: 7, tenantId: 3, name: role, email: `${role}@example.com`, phoneMasked: null, role, twoFactorChannel: null },
    })

    // Act
    const owner = renderWithPlugins(TeamView, {}, () => useAuthStore().startSession(session('owner')))
    const link = await screen.findByRole('link', { name: 'Agenda de Owner' })
    const ownerLinks = screen.getAllByRole('link', { name: /^Agenda de/ }).length
    owner.unmount()
    renderWithPlugins(TeamView, {}, () => useAuthStore().startSession(session('barber')))
    await waitFor(() => expect(screen.getByText('Owner')).toBeTruthy())

    // Assert
    expect(link.getAttribute('href')).toBe('/painel/equipe/1/agenda')
    expect(ownerLinks).toBe(2)
    expect(screen.queryByRole('link', { name: /^Agenda de/ })).toBeNull()
  })
})
