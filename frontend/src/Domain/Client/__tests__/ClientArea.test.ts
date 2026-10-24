import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import ClientAppointmentsView from '../ClientAppointmentsView.vue'
import ClientBarbershopsView from '../ClientBarbershopsView.vue'
import ClientProfileView from '../ClientProfileView.vue'
import { useClientStore } from '../client.store'
import { APPOINTMENTS, CLIENT, clientSessionBody, installFakeClientApi } from './fakeClientApi'
import { envelope, type FakeCall } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'
import * as download from '@/utils/download'

async function renderSignedIn(component: Parameters<typeof renderWithPlugins>[0], path: string) {
  const rendered = renderWithPlugins(component, {}, () => {
    const now = Date.now()
    const body = clientSessionBody()
    useClientStore().session = {
      accessToken: body.access_token,
      refreshToken: body.refresh_token,
      accessExpiresAt: now + 60_000,
      refreshExpiresAt: now + 120_000,
      client: { id: 11, name: 'Ana Souza', email: 'ana@example.com', phone: '11912345678', hasAccount: true, emailsEnabled: true },
    }
  })
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('client area 2.0', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('shows each barbershop with its logo, or its initials when it has none', async () => {
    // Arrange
    installFakeClientApi()

    // Act
    const { container } = await renderSignedIn(ClientAppointmentsView, '/conta')

    // Assert
    await screen.findByText('Barba')
    expect(container.querySelector('img[src="/storage/images/navalha.webp"]')).toBeTruthy()
    expect(screen.getByText('RC')).toBeTruthy()
    expect(screen.getByRole('navigation', { name: 'Menu da sua conta' })).toBeTruthy()
  })

  it('cancels an upcoming appointment after asking, and shows it cancelled', async () => {
    // Arrange
    let cancelled = false
    const { calls } = installFakeClientApi({
      'POST /client/appointments/2/cancel': () => {
        cancelled = true
        return { status: 204 }
      },
      'GET /client/appointments': () => ({
        status: 200,
        body: APPOINTMENTS.map((item) => (item.id === 2 && cancelled ? { ...item, status: 'cancelled' } : item)),
      }),
    })
    await renderSignedIn(ClientAppointmentsView, '/conta')
    await screen.findByText('Barba')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Cancelar horário' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Sim, cancelar' }))

    // Assert
    expect(await screen.findByText('Cancelado')).toBeTruthy()
    expect(calls.some((call: FakeCall) => call.method === 'POST' && call.path === '/client/appointments/2/cancel')).toBe(true)
    expect(screen.getByText('Nenhum horário marcado.')).toBeTruthy()
  })

  it('explains when it is too late to cancel from the app', async () => {
    // Arrange
    installFakeClientApi({ 'POST /client/appointments/2/cancel': () => envelope('CANCELLATION_TOO_LATE', 409) })
    await renderSignedIn(ClientAppointmentsView, '/conta')
    await screen.findByText('Barba')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Cancelar horário' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Sim, cancelar' }))

    // Assert
    expect((await screen.findByRole('alert')).textContent).toContain('fale com a barbearia pelo WhatsApp')
  })

  it('offers "Adicionar à agenda" for upcoming appointments', async () => {
    // Arrange
    installFakeClientApi()

    // Act
    await renderSignedIn(ClientAppointmentsView, '/conta')
    await screen.findByText('Barba')

    // Assert
    const google = new URL(screen.getByRole('link', { name: 'Google' }).getAttribute('href')!)
    expect(google.searchParams.get('dates')).toBe('20991021T150000/20991021T153000')
    expect(screen.getByRole('link', { name: 'Outro app (.ics)' }).getAttribute('href')).toMatch(/^data:text\/calendar/)
  })

  it('"Minhas barbearias" lists every barbershop where the client booked, to book again', async () => {
    // Arrange
    installFakeClientApi()

    // Act
    await renderSignedIn(ClientBarbershopsView, '/conta/barbearias')

    // Assert
    expect(await screen.findByRole('heading', { name: 'Navalha' })).toBeTruthy()
    expect(screen.getByRole('heading', { name: 'Rafael Costa' })).toBeTruthy()
    const bookLinks = screen.getAllByRole('link', { name: /^Agendar/ }).map((link) => link.getAttribute('href'))
    expect(bookLinks).toEqual(['/navalha/agendar', '/rafael/agendar'])
  })

  it('saves name and WhatsApp in "Minha conta"', async () => {
    // Arrange
    const { calls } = installFakeClientApi({
      'PATCH /client/me': (call: FakeCall) => ({
        status: 200,
        body: { ...CLIENT, name: call.body?.name, phone: '11988887777', emails_enabled: true },
      }),
    })
    await renderSignedIn(ClientProfileView, '/conta/perfil')

    // Act
    await fireEvent.update(screen.getByLabelText('Nome'), 'Ana Lima')
    await fireEvent.update(screen.getByLabelText('WhatsApp'), '(11) 98888-7777')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar dados' }))

    // Assert
    expect(await screen.findByText('Dados salvos.')).toBeTruthy()
    expect(calls.at(-1)?.body).toEqual({ name: 'Ana Lima', phone: '(11) 98888-7777' })
    expect(useClientStore().client?.name).toBe('Ana Lima')
  })

  it('says when the current password is wrong', async () => {
    // Arrange
    installFakeClientApi({
      'PUT /client/me/password': () => envelope('VALIDATION_FAILED', 422, null, { current_password: ['current_password'] }),
    })
    await renderSignedIn(ClientProfileView, '/conta/perfil')

    // Act
    await fireEvent.update(screen.getByLabelText('Senha atual'), 'errada123')
    await fireEvent.update(screen.getByLabelText('Nova senha'), 'novasenha1')
    await fireEvent.click(screen.getByRole('button', { name: 'Trocar senha' }))

    // Assert
    expect(await screen.findByText('Senha atual incorreta.')).toBeTruthy()
  })

  it('changing the password keeps this device signed in with the new session', async () => {
    // Arrange
    installFakeClientApi({
      'PUT /client/me/password': () => ({ status: 200, body: clientSessionBody('client-access-new', 'client-refresh-new') }),
    })
    await renderSignedIn(ClientProfileView, '/conta/perfil')

    // Act
    await fireEvent.update(screen.getByLabelText('Senha atual'), 'segredo123')
    await fireEvent.update(screen.getByLabelText('Nova senha'), 'novasenha1')
    await fireEvent.click(screen.getByRole('button', { name: 'Trocar senha' }))

    // Assert
    expect(await screen.findByText('Senha alterada. Os outros aparelhos foram desconectados.')).toBeTruthy()
    expect(useClientStore().session?.refreshToken).toBe('client-refresh-new')
  })

  it('deletes the account with the password and forgets the session', async () => {
    // Arrange
    const { calls } = installFakeClientApi({ 'DELETE /client/me': () => ({ status: 204 }) })
    const { router } = await renderSignedIn(ClientProfileView, '/conta/perfil')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Excluir minha conta' }))
    await fireEvent.update(screen.getByLabelText('Digite sua senha para confirmar'), 'segredo123')
    await fireEvent.click(screen.getByRole('button', { name: 'Excluir definitivamente' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('client-login'))
    expect(router.currentRoute.value.query.conta).toBe('excluida')
    expect(calls.at(-1)).toMatchObject({ method: 'DELETE', path: '/client/me', body: { password: 'segredo123' } })
    expect(useClientStore().session).toBeNull()
    expect(localStorage.getItem('linkcut.client-session')).toBeNull()
  })

  it('"Baixar meus dados" downloads the account and appointments as JSON', async () => {
    // Arrange
    const exported = { client: { email: 'ana@example.com' }, appointments: [] }
    installFakeClientApi({ 'GET /client/me/export': () => ({ status: 200, body: exported }) })
    const save = vi.spyOn(download, 'downloadJson').mockImplementation(() => undefined)
    await renderSignedIn(ClientProfileView, '/conta/perfil')

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: /Baixar meus dados/ }))

    // Assert
    await vi.waitFor(() => expect(save).toHaveBeenCalledWith('linkcut-meus-dados.json', exported))
    save.mockRestore()
  })
})
