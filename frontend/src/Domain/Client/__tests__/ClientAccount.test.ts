import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import ClientAuthView from '../ClientAuthView.vue'
import ClientAppointmentsView from '../ClientAppointmentsView.vue'
import { installClientGuard } from '../client.guard'
import { useClientStore } from '../client.store'
import { CLIENT_PASSWORD, RIGHT_CODE, clientSessionBody, installFakeClientApi } from './fakeClientApi'
import { routes } from '@/router'
import { renderWithPlugins } from '@/utils/testRender'
import { useAuthStore } from '@/Domain/Identity/auth.store'

async function renderAt(component: Parameters<typeof renderWithPlugins>[0], path: string, before: () => void = () => {}) {
  const rendered = renderWithPlugins(component, {}, before)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

async function typeCode(code: string): Promise<void> {
  const [firstBox] = await screen.findAllByRole('textbox', { name: /Código de verificação/ })
  await fireEvent.paste(firstBox!, { clipboardData: { getData: () => code } })
}

function signIn(): void {
  const now = Date.now()
  const body = clientSessionBody()
  useClientStore().session = {
    accessToken: body.access_token,
    refreshToken: body.refresh_token,
    accessExpiresAt: now + 60_000,
    refreshExpiresAt: now + 120_000,
    client: { id: 11, name: 'Ana Souza', email: 'ana@example.com', phone: '11912345678', hasAccount: true, emailsEnabled: true },
  }
}

describe('client area', () => {
  beforeEach(() => {
    localStorage.clear()
    sessionStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('completes the sign-up with the code from the e-mail and opens "Meus horários"', async () => {
    // Arrange
    const { calls } = installFakeClientApi()
    const { router } = await renderAt(ClientAuthView, '/conta/finalizar?email=ana%40example.com')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar código' }))
    await typeCode(RIGHT_CODE)
    await fireEvent.update(screen.getByLabelText('Crie uma senha'), CLIENT_PASSWORD)
    await fireEvent.click(screen.getByRole('button', { name: 'Criar senha e entrar' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/conta'))
    expect(calls[0]?.body).toEqual({ email: 'ana@example.com', purpose: 'registration' })
    expect(calls[1]?.body).toEqual({ email: 'ana@example.com', code: RIGHT_CODE, password: CLIENT_PASSWORD })
    expect(JSON.parse(localStorage.getItem('linkcut.client-session') ?? '{}').accessToken).toBe('client-access-1')
  })

  it('a wrong code says how many attempts are left', async () => {
    // Arrange
    installFakeClientApi()
    await renderAt(ClientAuthView, '/conta/finalizar?email=ana%40example.com')
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar código' }))

    // Act
    await typeCode('000000')
    await fireEvent.update(screen.getByLabelText('Crie uma senha'), CLIENT_PASSWORD)
    await fireEvent.click(screen.getByRole('button', { name: 'Criar senha e entrar' }))

    // Assert
    expect(await screen.findByText('Código incorreto. Você tem mais 4 tentativa(s).')).toBeTruthy()
    expect(localStorage.getItem('linkcut.client-session')).toBeNull()
  })

  it('signs in with e-mail and password and goes back to the page asked for', async () => {
    // Arrange
    installFakeClientApi()
    const { router } = await renderAt(ClientAuthView, '/conta/entrar?redirect=/conta')

    // Act
    await fireEvent.update(screen.getByLabelText('E-mail'), 'ana@example.com')
    await fireEvent.update(screen.getByLabelText('Senha'), 'errada123')
    await fireEvent.click(screen.getByRole('button', { name: 'Entrar' }))
    await screen.findByText('E-mail ou senha incorretos.')
    await fireEvent.update(screen.getByLabelText('Senha'), CLIENT_PASSWORD)
    await fireEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/conta'))
  })

  it('"Esqueci minha senha" sets a new password with a code', async () => {
    // Arrange
    const { calls } = installFakeClientApi()
    const { router } = await renderAt(ClientAuthView, '/conta/entrar')
    await fireEvent.update(screen.getByLabelText('E-mail'), 'ana@example.com')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Esqueci minha senha' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar código' }))
    await typeCode(RIGHT_CODE)
    await fireEvent.update(screen.getByLabelText('Crie uma senha'), 'novasenha1')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar nova senha e entrar' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/conta'))
    expect(calls[0]?.body).toEqual({ email: 'ana@example.com', purpose: 'password' })
    expect(calls[1]?.path).toBe('/client/password')
  })

  it('lists upcoming appointments with directions and past ones with "Agendar de novo", using the client token', async () => {
    // Arrange
    const { calls } = installFakeClientApi()

    // Act
    await renderAt(ClientAppointmentsView, '/conta', () => {
      signIn()
      useAuthStore().session = null
    })

    // Assert
    expect(await screen.findByText('Barba')).toBeTruthy()
    expect(screen.getByText('Confirmado')).toBeTruthy()
    expect(screen.getByRole('link', { name: /Como chegar/ }).getAttribute('href')).toContain('google.com/maps')
    expect(screen.getByRole('link', { name: 'Falar no WhatsApp' }).getAttribute('href')).toBe('https://wa.me/5511999998888')
    expect(screen.getByRole('link', { name: /Agendar de novo/ }).getAttribute('href')).toBe('/rafael/agendar')
    expect(calls[0]?.headers.Authorization).toBe('Bearer client-access-1')
  })
})

describe('client guard', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('sends a visitor without the client session to the client login', async () => {
    // Arrange
    const router = createRouter({ history: createMemoryHistory(), routes })
    installClientGuard(router)

    // Act
    await router.push('/conta')

    // Assert
    expect(router.currentRoute.value.name).toBe('client-login')
    expect(router.currentRoute.value.query.redirect).toBe('/conta')
  })

  it('a barber session does not open the client area', async () => {
    // Arrange
    const router = createRouter({ history: createMemoryHistory(), routes })
    installClientGuard(router)
    const now = Date.now()
    useAuthStore().session = {
      accessToken: 'barber',
      refreshToken: 'barber-refresh',
      accessExpiresAt: now + 60_000,
      refreshExpiresAt: now + 120_000,
      user: { id: 7, tenantId: 3, name: 'Rafael', email: 'rafael@linkcut.app', phoneMasked: null, role: 'owner', twoFactorChannel: null },
    }

    // Act
    await router.push('/conta')

    // Assert
    expect(router.currentRoute.value.name).toBe('client-login')
  })
})
