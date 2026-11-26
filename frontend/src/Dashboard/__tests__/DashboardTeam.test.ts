import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, within } from '@testing-library/vue'
import DashboardView from '../DashboardView.vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { createFinanceRoutes } from '@/Domain/Finance/__tests__/fakeFinanceApi'
import { createScheduleRoutes, requestRow, type RequestRow } from '@/Domain/Schedule/__tests__/fakeScheduleApi'
import { installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { envelope, type FakeCall } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const TODAY = '2026-10-17'

const member = (id: number, name: string, userId: number | null) => ({
  id,
  tenant_id: 3,
  user_id: userId,
  name,
  avatar: null,
  active: true,
  pending_invite: false,
  permissions: { see_prices: true, see_own_commission: true, see_unit_agenda: true },
})

/** Today: Lucas with the owner (staff 1) at 18:00 and Pedro with Bruno (staff 2) at 19:00. Bruno is busy for any move. */
function installTeamApis(staffCount: number) {
  const requests: RequestRow[] = [
    requestRow({ id: 3, client_name: 'Lucas Andrade', date: TODAY, starts_at: '18:00', status: 'confirmed', staff_id: 1 }),
    requestRow({ id: 4, client_name: 'Pedro Martins', date: TODAY, starts_at: '19:00', status: 'confirmed', staff_id: 2 }),
  ]
  const staff = [member(1, 'Rafael', 7), member(2, 'Bruno', 8), member(3, 'Caio', null)].slice(0, staffCount)
  const schedule = createScheduleRoutes({ today: TODAY, requests })
  const finance = createFinanceRoutes({ today: TODAY })
  return installFakeSettingsApi(undefined, {
    ...finance.routes,
    ...schedule.routes,
    'GET /team/staff': () => ({ status: 200, body: staff }),
    'POST /schedule/requests/:id/reassign': ({ params, body }: FakeCall) => {
      if (body?.staff_id === 2) return envelope('SLOT_UNAVAILABLE', 409)
      const row = requests.find((item) => item.id === Number(params.id))!
      row.staff_id = Number(body?.staff_id)
      return { status: 200, body: row }
    },
  })
}

function signInAs(role: 'owner' | 'barber'): void {
  const now = Date.now()
  useAuthStore().session = {
    accessToken: 'access',
    refreshToken: 'refresh',
    accessExpiresAt: now + 60_000,
    refreshExpiresAt: now + 120_000,
    user: { id: role === 'owner' ? 7 : 8, tenantId: 3, name: role, email: `${role}@linkcut.app`, phoneMasked: null, role, twoFactorChannel: null },
  }
}

const cardOf = async (client: string) => (await screen.findByRole('heading', { name: client })).closest('article') as HTMLElement

describe('Dashboard with a team', () => {
  beforeEach(() => {
    sessionStorage.clear()
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(new Date(2026, 9, 17, 12, 0))
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('the owner sees who attends each appointment and can pass it to another barber', async () => {
    // Arrange
    const { calls } = installTeamApis(3)
    const { router } = renderWithPlugins(DashboardView, {}, () => signInAs('owner'))
    await router.push('/painel')
    const lucas = await cardOf('Lucas Andrade')
    await within(lucas).findByText('Com Rafael')

    // Act
    await fireEvent.click(within(lucas).getByRole('button', { name: /Passar o atendimento de Lucas Andrade/ }))
    const sheet = await screen.findByRole('dialog', { name: 'Passar para outro barbeiro' })
    expect(within(sheet).queryByRole('button', { name: 'Rafael' })).toBeNull()
    await fireEvent.click(within(sheet).getByRole('button', { name: 'Caio' }))

    // Assert
    await vi.waitFor(() => expect(within(lucas).getByText('Com Caio')).toBeTruthy())
    expect(calls.find((call) => call.path === '/schedule/requests/3/reassign')?.body).toEqual({ staff_id: 3 })
  })

  it('a busy barber shows the error and the appointment stays with its barber', async () => {
    // Arrange
    installTeamApis(3)
    const { router } = renderWithPlugins(DashboardView, {}, () => signInAs('owner'))
    await router.push('/painel')
    const lucas = await cardOf('Lucas Andrade')
    await within(lucas).findByText('Com Rafael')
    await fireEvent.click(within(lucas).getByRole('button', { name: /Passar o atendimento de Lucas Andrade/ }))
    const sheet = await screen.findByRole('dialog', { name: 'Passar para outro barbeiro' })

    // Act
    await fireEvent.click(within(sheet).getByRole('button', { name: 'Bruno' }))

    // Assert
    expect((await within(sheet).findByRole('alert')).textContent).toContain('Bruno não está livre neste horário')
    expect(within(lucas).getByText('Com Rafael')).toBeTruthy()
  })

  it('the "Barbeiro" filter shows only the appointments of that barber', async () => {
    // Arrange
    installTeamApis(3)
    const { router } = renderWithPlugins(DashboardView, {}, () => signInAs('owner'))
    await router.push('/painel')
    await cardOf('Pedro Martins')

    // Act
    await fireEvent.update(await screen.findByLabelText('Barbeiro'), '2')

    // Assert
    await vi.waitFor(() => expect(screen.queryByRole('heading', { name: 'Lucas Andrade' })).toBeNull())
    expect(screen.getByRole('heading', { name: 'Pedro Martins' })).toBeTruthy()
    expect(sessionStorage.getItem('linkcut.agenda-staff')).toBe('2')
  })

  it('a barber sees neither the names, the filter nor the action', async () => {
    // Arrange
    installTeamApis(3)
    const { router } = renderWithPlugins(DashboardView, {}, () => signInAs('barber'))

    // Act
    await router.push('/painel')
    await cardOf('Pedro Martins')

    // Assert
    expect(screen.queryByText(/^Com /)).toBeNull()
    expect(screen.queryByLabelText('Barbeiro')).toBeNull()
    expect(screen.queryByRole('button', { name: /Passar o atendimento/ })).toBeNull()
  })

  it('a unit with one barber shows no name and no filter', async () => {
    // Arrange
    installTeamApis(1)
    const { router } = renderWithPlugins(DashboardView, {}, () => signInAs('owner'))

    // Act
    await router.push('/painel')
    await cardOf('Lucas Andrade')

    // Assert
    expect(screen.queryByText('Com Rafael')).toBeNull()
    expect(screen.queryByLabelText('Barbeiro')).toBeNull()
  })
})
