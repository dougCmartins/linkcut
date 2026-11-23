import { describe, expect, it } from 'vitest'
import { installFakeApi } from '@/utils/testFetch'
import { acceptStaffInvite, fetchStaff, inviteStaff } from '../team.api'

describe('team api', () => {
  it('maps staff list from snake_case', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /team/staff': () => ({
        status: 200,
        body: [
          {
            id: 1,
            tenant_id: 3,
            user_id: 7,
            name: 'Owner',
            avatar: null,
            active: true,
            pending_invite: false,
            permissions: { see_prices: false, see_own_commission: false, see_unit_agenda: false },
          },
        ],
      }),
    })

    // Act
    const staff = await fetchStaff()

    // Assert
    expect(calls[0]?.path).toBe('/team/staff')
    expect(staff[0]).toMatchObject({
      id: 1,
      tenantId: 3,
      userId: 7,
      pendingInvite: false,
      permissions: { seePrices: false },
    })
  })

  it('invites a barber and accepts the invite session', async () => {
    // Arrange
    installFakeApi({
      'POST /team/staff': () => ({
        status: 201,
        body: {
          id: 2,
          tenant_id: 3,
          user_id: null,
          name: 'João',
          avatar: null,
          active: true,
          pending_invite: true,
          permissions: { see_prices: false, see_own_commission: false, see_unit_agenda: false },
        },
      }),
      'POST /public/staff-invites/tok': () => ({
        status: 201,
        body: {
          access_token: 'a',
          token_type: 'Bearer',
          expires_in: 3600,
          refresh_token: 'r',
          refresh_expires_in: 86400,
          user: {
            id: 9,
            tenant_id: 3,
            name: 'João',
            email: 'joao@example.com',
            phone_masked: null,
            role: 'barber',
            two_factor_channel: null,
          },
        },
      }),
    })

    // Act
    const invited = await inviteStaff('João', 'joao@example.com')
    const session = await acceptStaffInvite('tok', 'João', 'password1')

    // Assert
    expect(invited.pendingInvite).toBe(true)
    expect(session.user.role).toBe('barber')
  })
})
