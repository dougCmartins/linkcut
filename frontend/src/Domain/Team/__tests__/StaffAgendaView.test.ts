import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, within } from '@testing-library/vue'
import StaffAgendaView from '../StaffAgendaView.vue'
import { installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import type { FakeCall } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const WEEK = Array.from({ length: 7 }, (_, weekday) => ({
  weekday,
  is_open: weekday !== 0,
  opens_at: '09:00',
  closes_at: '19:00',
  break_starts_at: null,
  break_ends_at: null,
}))

function installAgendaApi() {
  let blocks = [{ id: 4, staff_id: 2, starts_at: '2026-11-26 13:00', ends_at: '2026-11-26 18:00', recurrence: 'weekly', weekdays: [3], note: null }]
  return installFakeSettingsApi(undefined, {
    'GET /team/staff': () => ({
      status: 200,
      body: [{ id: 2, tenant_id: 3, user_id: null, name: 'Bruno', avatar: null, active: true, pending_invite: false, permissions: {} }],
    }),
    'GET /team/staff/:id/hours': () => ({ status: 200, body: WEEK }),
    'PUT /team/staff/:id/hours': ({ body }: FakeCall) => ({ status: 200, body: body?.weekly_hours }),
    'GET /team/staff/:id/services': () => ({ status: 200, body: { service_ids: [1, 2] } }),
    'PUT /team/staff/:id/services': ({ body }: FakeCall) => ({ status: 200, body: { service_ids: body?.service_ids } }),
    'GET /team/staff/:id/blocks': () => ({ status: 200, body: blocks }),
    'POST /team/staff/:id/blocks': ({ body }: FakeCall) => {
      const created = { id: 5, staff_id: 2, ...(body as object) }
      blocks = [...blocks, created as (typeof blocks)[number]]
      return { status: 201, body: created }
    },
    'DELETE /team/staff/:id/blocks/:blockId': () => ({ status: 204 }),
  })
}

async function renderAgenda() {
  const rendered = renderWithPlugins(StaffAgendaView, {}, () => {
    useAuthStore().startSession({
      accessToken: 'a',
      refreshToken: 'r',
      accessExpiresAt: Date.now() + 60_000,
      refreshExpiresAt: Date.now() + 120_000,
      user: { id: 7, tenantId: 3, name: 'Dona', email: 'dona@example.com', phoneMasked: null, role: 'owner', twoFactorChannel: null },
    })
  })
  await rendered.router.push('/painel/equipe/2/agenda')
  await screen.findByText('Horários')
  return rendered
}

describe('StaffAgendaView', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('adds a break on Monday, repeats it and saves the hours', async () => {
    // Arrange
    const { calls } = installAgendaApi()
    await renderAgenda()
    const monday = screen.getByText('Segunda-feira').closest('li') as HTMLElement

    // Act
    await fireEvent.click(within(monday).getByRole('switch', { name: /Intervalo/ }))
    await fireEvent.click(within(monday).getByRole('button', { name: 'Repetir nos outros dias' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    await vi.waitFor(() => expect(calls.some((call) => call.method === 'PUT' && call.path.endsWith('/hours'))).toBe(true))
    const sent = calls.find((call) => call.method === 'PUT' && call.path.endsWith('/hours'))?.body?.weekly_hours as Record<string, unknown>[]
    expect(sent.find((day) => day.weekday === 1)).toMatchObject({ break_starts_at: '12:00', break_ends_at: '13:00' })
    expect(sent.find((day) => day.weekday === 5)).toMatchObject({ break_starts_at: '12:00', break_ends_at: '13:00' })
    expect(sent.find((day) => day.weekday === 0)).toMatchObject({ is_open: false, break_starts_at: null })
    expect(calls.some((call) => call.method === 'PUT' && call.path.endsWith('/services'))).toBe(false)
  })

  it('a break outside the working period blocks the save', async () => {
    // Arrange
    installAgendaApi()
    await renderAgenda()
    const monday = screen.getByText('Segunda-feira').closest('li') as HTMLElement
    await fireEvent.click(within(monday).getByRole('switch', { name: /Intervalo/ }))

    // Act
    await fireEvent.update(within(monday).getByLabelText('Fim do intervalo'), '20:00')

    // Assert
    expect(within(monday).getByRole('alert').textContent).toContain('dentro do expediente')
    expect((screen.getByRole('button', { name: 'Salvar' }) as HTMLButtonElement).disabled).toBe(true)
  })

  it('turns a service off and saves; with no service left it warns and cannot save', async () => {
    // Arrange
    const { calls } = installAgendaApi()
    await renderAgenda()

    // Act
    await fireEvent.click(screen.getByRole('switch', { name: 'Barba' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))
    await vi.waitFor(() => expect(calls.some((call) => call.method === 'PUT' && call.path.endsWith('/services'))).toBe(true))
    await fireEvent.click(screen.getByRole('switch', { name: 'Corte' }))

    // Assert
    expect(calls.find((call) => call.method === 'PUT' && call.path.endsWith('/services'))?.body).toEqual({ service_ids: [1] })
    expect(screen.getByText(/Ligue pelo menos um serviço/)).toBeTruthy()
    expect((screen.getByRole('button', { name: 'Salvar' }) as HTMLButtonElement).disabled).toBe(true)
  })

  it('creates a weekly block with its weekdays and removes a block with "Desbloquear"', async () => {
    // Arrange
    const { calls } = installAgendaApi()
    await renderAgenda()
    expect(screen.getByText('toda quarta-feira, 13:00–18:00')).toBeTruthy()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Bloquear horário' }))
    await fireEvent.click(screen.getByRole('switch', { name: 'Repetir toda semana' }))
    await fireEvent.click(screen.getByRole('checkbox', { name: 'sexta-feira' }))
    await fireEvent.update(screen.getByLabelText('A partir de'), '2026-11-27')
    await fireEvent.click(screen.getByRole('button', { name: 'Bloquear' }))
    await screen.findByText('toda sexta-feira, 13:00–18:00')
    await fireEvent.click(screen.getAllByRole('button', { name: 'Desbloquear' })[0]!)

    // Assert
    expect(calls.find((call) => call.method === 'POST')?.body).toEqual({
      starts_at: '2026-11-27 13:00',
      ends_at: '2026-11-27 18:00',
      recurrence: 'weekly',
      weekdays: [5],
      note: null,
    })
    await vi.waitFor(() => expect(calls.some((call) => call.method === 'DELETE' && call.path === '/team/staff/2/blocks/4')).toBe(true))
    await vi.waitFor(() => expect(screen.queryByText('toda quarta-feira, 13:00–18:00')).toBeNull())
  })
})
