import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import TeamView from '../TeamView.vue'
import AcceptInvitationView from '../AcceptInvitationView.vue'
import PanelHomeView from '@/Dashboard/PanelHomeView.vue'
import { installAuthGuard } from '@/Domain/Identity/auth.guard'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { sessionBody } from '@/Domain/Identity/__tests__/fakeIdentityApi'
import { routes } from '@/router'
import { envelope, installFakeApi } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

/** "Minha equipe", the invitation link and the barber's dashboard (docs/decisoes/0001 P3). */
const member = (id: number, name: string, status: string, isYou = false, seePrices = false) => ({
  id,
  name,
  email: `${name.toLowerCase()}@example.com`,
  status,
  is_you: isYou,
  permissions: { see_prices: seePrices, see_own_commission: false, see_unit_agenda: false },
})

function signInAs(role: 'owner' | 'barber'): void {
  const now = Date.now()
  useAuthStore().session = {
    accessToken: 'access',
    refreshToken: 'refresh',
    accessExpiresAt: now + 60_000,
    refreshExpiresAt: now + 120_000,
    user: { id: 7, tenantId: 3, name: 'João Barbeiro', email: 'joao@example.com', phoneMasked: null, role, twoFactorChannel: null },
  }
}

describe('team', () => {
  beforeEach(() => {
    localStorage.clear()
    sessionStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('the owner invites a barber and turns a switch on for them', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /team/staff': () => ({ status: 200, body: [member(1, 'Rafael', 'active', true)] }),
      'POST /team/staff': () => ({ status: 201, body: member(2, 'Joao', 'invited') }),
      'PUT /team/staff/:id': () => ({ status: 200, body: member(2, 'Joao', 'invited', false, true) }),
    })
    renderWithPlugins(TeamView)
    await screen.findByText('Rafael')

    // Act
    await fireEvent.update(screen.getByLabelText('Nome'), 'Joao')
    await fireEvent.update(screen.getByLabelText('E-mail'), 'joao@example.com')
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar convite' }))
    await screen.findByText('Convite enviado para joao@example.com.')
    await fireEvent.click(screen.getAllByRole('switch', { name: 'Ver preços e valores' })[0]!)

    // Assert
    await waitFor(() => expect(calls.some((call) => call.method === 'PUT')).toBe(true))
    expect(calls.find((call) => call.method === 'POST')?.body).toEqual({ name: 'Joao', email: 'joao@example.com' })
    expect(calls.find((call) => call.method === 'PUT')).toMatchObject({
      path: '/team/staff/2',
      body: { active: true, permissions: { see_prices: true, see_own_commission: false, see_unit_agenda: false } },
    })
    expect(screen.getByRole('button', { name: 'Reenviar convite' })).toBeTruthy()
    // The owner's own row has no switches (they cannot turn themselves off).
    expect(screen.getAllByRole('switch', { name: 'Acesso ligado' })).toHaveLength(1)
  })

  it('the same e-mail twice says it is already in the team', async () => {
    // Arrange
    installFakeApi({
      'GET /team/staff': () => ({ status: 200, body: [] }),
      'POST /team/staff': () => envelope('STAFF_EMAIL_TAKEN', 409),
    })
    renderWithPlugins(TeamView)

    // Act
    await fireEvent.update(screen.getByLabelText('Nome'), 'Joao')
    await fireEvent.update(screen.getByLabelText('E-mail'), 'joao@example.com')
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar convite' }))

    // Assert
    expect(await screen.findByText('Este e-mail já está na equipe.')).toBeTruthy()
  })

  it('the invitation link creates the password, signs the barber in and opens the dashboard', async () => {
    // Arrange
    const { calls } = installFakeApi({ 'POST /team/invitations/accept': () => ({ status: 201, body: sessionBody() }) })
    const { router } = renderWithPlugins(AcceptInvitationView)
    await router.push('/equipe/convite?token=abc123')

    // Act
    await fireEvent.update(await screen.findByLabelText(/Crie uma senha/), 'senha-forte-1')
    await fireEvent.click(screen.getByRole('checkbox'))
    await fireEvent.click(screen.getByRole('button', { name: 'Aceitar e entrar' }))

    // Assert
    await waitFor(() => expect(router.currentRoute.value.path).toBe('/painel'))
    expect(calls[0]?.body).toEqual({ token: 'abc123', password: 'senha-forte-1', accept_terms: true })
    expect(useAuthStore().session?.accessToken).toBe('access-1')
  })

  it('a used or expired invitation says so', async () => {
    // Arrange
    installFakeApi({ 'POST /team/invitations/accept': () => envelope('INVITATION_INVALID', 422) })
    const { router } = renderWithPlugins(AcceptInvitationView)
    await router.push('/equipe/convite?token=old')

    // Act
    await fireEvent.update(await screen.findByLabelText(/Crie uma senha/), 'senha-forte-1')
    await fireEvent.click(screen.getByRole('checkbox'))
    await fireEvent.click(screen.getByRole('button', { name: 'Aceitar e entrar' }))

    // Assert
    expect(await screen.findByText(/Este convite não vale mais/)).toBeTruthy()
  })

  it("a barber of the team sees their agenda, never the owner's dashboard, finance or settings", async () => {
    // Arrange
    const { calls } = installFakeApi({
      'GET /schedule/requests': () => ({ status: 200, body: [] }),
      'GET /schedule/today': () => ({ status: 200, body: [] }),
      'GET /tenant': () => ({ status: 200, body: { id: 3, name: 'Rafael', slug: 'rafael', onboarded: true } }),
    })

    // Act
    renderWithPlugins(PanelHomeView, {}, () => signInAs('barber'))

    // Assert
    expect(await screen.findByRole('heading', { name: 'Olá, João!' })).toBeTruthy()
    expect(await screen.findByText('Nenhum atendimento hoje.')).toBeTruthy()
    expect(calls.some((call) => call.path.startsWith('/finance') || call.path.startsWith('/settings'))).toBe(false)
  })

  it("the owner's pages send a barber of the team back to their agenda", async () => {
    // Arrange
    setActivePinia(createPinia())
    signInAs('barber')
    installFakeApi({ 'GET /tenant': () => ({ status: 200, body: { id: 3, name: 'Rafael', slug: 'rafael', onboarded: true } }) })
    const router = createRouter({ history: createMemoryHistory(), routes })
    installAuthGuard(router)

    // Act
    await router.push('/painel/configuracoes')

    // Assert
    expect(router.currentRoute.value.path).toBe('/painel')
  })
})
