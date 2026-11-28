import { afterEach, describe, expect, it, vi } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import TeamView from '../TeamView.vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { envelope, installFakeApi, type FakeCall } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

/** "Mover para outra filial" and the current moves in "Minha equipe" (docs/decisoes/0003 P3). */
const permissions = { see_prices: false, see_own_commission: true, see_unit_agenda: false }
const STAFF = [
  { id: 1, tenant_id: 3, user_id: 7, name: 'Rafael', avatar: null, active: true, pending_invite: false, permissions },
  { id: 2, tenant_id: 3, user_id: 8, name: 'João', avatar: null, active: true, pending_invite: false, permissions },
  { id: 3, tenant_id: 3, user_id: 9, name: 'Pedro', avatar: null, active: true, pending_invite: false, permissions },
]
const UNITS = [
  { id: 3, name: 'Rafael', slug: 'rafael', is_main: true },
  { id: 4, name: 'Rafael Centro', slug: 'rafael-centro', is_main: false },
]
const HOURS = Array.from({ length: 7 }, (_, weekday) => ({
  weekday,
  is_open: weekday !== 0,
  opens_at: '09:00',
  closes_at: '18:00',
  break_starts_at: null,
  break_ends_at: null,
}))
const LOAN = {
  id: 50, kind: 'loan', starts_on: '2026-10-19', ends_on: '2026-10-23', barber_name: 'João',
  from_unit_id: 3, from_unit_name: 'Rafael', to_unit_id: 4, to_unit_name: 'Rafael Centro',
}

function signIn(role: 'owner' | 'barber'): void {
  useAuthStore().startSession({
    accessToken: 'a',
    refreshToken: 'r',
    accessExpiresAt: Date.now() + 60_000,
    refreshExpiresAt: Date.now() + 120_000,
    user: { id: 7, tenantId: 3, name: 'Rafael', email: 'r@example.com', phoneMasked: null, role, twoFactorChannel: null, availableChannels: [] },
  })
}

function installApi(options: { type?: 'individual' | 'multi_unit'; moves?: unknown[]; move?: (call: FakeCall) => { status: number; body?: unknown } } = {}) {
  return installFakeApi({
    'GET /team/staff': () => ({ status: 200, body: STAFF }),
    'GET /organization': () => ({
      status: 200,
      body: { id: 1, type: options.type ?? 'multi_unit', name: 'Rafael', slug: 'rafael', units: options.type === 'individual' ? [UNITS[0]] : UNITS },
    }),
    'GET /team/moves': () => ({ status: 200, body: options.moves ?? [] }),
    'GET /team/staff/:id/move-options': () => ({ status: 200, body: { weekly_hours: HOURS, services: [{ id: 41, name: 'Corte' }, { id: 42, name: 'Barba' }] } }),
    'POST /team/staff/:id/move': options.move ?? (() => ({ status: 201, body: {} })),
    'POST /schedule/requests/:id/reassign': () => ({ status: 200, body: {} }),
    'PATCH /team/moves/:id/extend': () => ({ status: 200, body: {} }),
    'POST /team/moves/:id/end': () => ({ status: 201, body: {} }),
    'POST /team/moves/:id/make-permanent': () => ({ status: 201, body: {} }),
  })
}

async function openMoveFor(name: string) {
  await screen.findByText(name)
  const buttons = await screen.findAllByRole('button', { name: /Mover para outra filial/ })
  const index = STAFF.findIndex((member) => member.name === name)
  await userEvent.click(buttons[index]!)
  return screen.findByRole('dialog')
}

async function walkToSummary(options: { loanUntil?: string; reset?: boolean } = {}) {
  if (options.loanUntil) {
    await userEvent.click(screen.getByRole('radio', { name: /Empréstimo/ }))
    await userEvent.type(screen.getByLabelText('Último dia na outra filial'), options.loanUntil)
  }
  await userEvent.click(screen.getByRole('button', { name: 'Continuar' }))
  await screen.findByText(/Já vem com o horário da filial de destino/)
  await userEvent.click(screen.getByRole('button', { name: 'Continuar' }))
  await userEvent.click(screen.getByRole('button', { name: 'Continuar' }))
  if (options.reset) await userEvent.click(screen.getByRole('radio', { name: 'Começar sem permissões' }))
  await userEvent.click(screen.getByRole('button', { name: 'Continuar' }))
}

describe('move a barber to another unit', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('sends the destination, the kind, the hours, all the services and the switches chosen in the steps', async () => {
    // Arrange
    const { calls } = installApi()
    renderWithPlugins(TeamView, {}, () => signIn('owner'))
    await openMoveFor('João')

    // Act
    await walkToSummary({ loanUntil: '2026-10-23', reset: true })
    expect(screen.getByText('João atende em Rafael Centro até 2026-10-23 e depois volta.')).toBeTruthy()
    await userEvent.click(screen.getByRole('button', { name: 'Confirmar' }))

    // Assert
    await screen.findByText('João foi movido.')
    expect(calls.find((call) => call.path.startsWith('/team/staff/2/move-options'))?.path).toBe('/team/staff/2/move-options?to_unit_id=4')
    const sent = calls.find((call) => call.method === 'POST' && call.path === '/team/staff/2/move')?.body
    expect(sent).toMatchObject({ to_unit_id: 4, kind: 'loan', ends_on: '2026-10-23', service_ids: [41, 42], permissions: 'reset' })
    expect((sent?.weekly_hours as unknown[]).length).toBe(7)
  })

  it('lists the bookings that keep the barber here and passes one to another barber', async () => {
    // Arrange
    const { calls } = installApi({
      move: () => envelope('STAFF_HAS_UPCOMING_APPOINTMENTS', 409, { appointments: [{ id: 90, date: '2026-10-21', starts_at: '14:00', client_name: 'Ana Souza' }] }),
    })
    renderWithPlugins(TeamView, {}, () => signIn('owner'))
    await openMoveFor('João')
    await walkToSummary()

    // Act
    await userEvent.click(screen.getByRole('button', { name: 'Confirmar' }))
    await screen.findByText('2026-10-21 · 14:00 · Ana Souza')
    await userEvent.click(screen.getByRole('button', { name: 'Passar' }))

    // Assert
    await waitFor(() => expect(screen.queryByText('2026-10-21 · 14:00 · Ana Souza')).toBeNull())
    expect(calls.find((call) => call.path === '/schedule/requests/90/reassign')?.body).toEqual({ staff_id: 1 })
    // Every booking passed: the move can be confirmed again.
    expect(screen.getByRole('button', { name: 'Confirmar' })).toBeTruthy()
  })

  it('a loan can be extended, ended now or made permanent from "Minha equipe"', async () => {
    // Arrange
    const { calls } = installApi({ moves: [LOAN] })
    renderWithPlugins(TeamView, {}, () => signIn('owner'))
    await screen.findByText('João: emprestado para Rafael Centro até 2026-10-23')

    // Act
    await userEvent.click(screen.getByRole('button', { name: 'Estender' }))
    await userEvent.clear(screen.getByLabelText('Nova data de fim'))
    await userEvent.type(screen.getByLabelText('Nova data de fim'), '2026-10-30')
    await userEvent.click(screen.getByRole('button', { name: 'Salvar data' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Encerrar agora' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Tornar definitivo' }))

    // Assert
    await waitFor(() => expect(calls.some((call) => call.path === '/team/moves/50/make-permanent')).toBe(true))
    expect(calls.find((call) => call.method === 'PATCH')).toMatchObject({ path: '/team/moves/50/extend', body: { ends_on: '2026-10-30' } })
    expect(calls.some((call) => call.method === 'POST' && call.path === '/team/moves/50/end')).toBe(true)
  })

  it('a barber of the team and a barber on their own see no move at all', async () => {
    // Arrange
    installApi({ moves: [LOAN] })
    const barber = renderWithPlugins(TeamView, {}, () => signIn('barber'))
    await screen.findByText('João')

    // Assert
    expect(screen.queryByRole('button', { name: /Mover para outra filial/ })).toBeNull()
    expect(screen.queryByText(/emprestado para/)).toBeNull()
    barber.unmount()

    // Arrange: the owner of an individual account
    installApi({ type: 'individual' })
    renderWithPlugins(TeamView, {}, () => signIn('owner'))
    await screen.findByText('João')

    // Assert
    expect(screen.queryByRole('button', { name: /Mover para outra filial/ })).toBeNull()
  })
})
