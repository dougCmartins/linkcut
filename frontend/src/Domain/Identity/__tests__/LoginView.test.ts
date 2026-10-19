import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import LoginView from '../LoginView.vue'
import { useAuthStore } from '../auth.store'
import { renderWithPlugins } from '@/utils/testRender'
import { installFakeIdentityApi, PASSWORD, RIGHT_CODE } from './fakeIdentityApi'

const EMAIL = 'rafael@linkcut.app'

async function renderLogin(path = '/entrar?redirect=/painel/configuracoes') {
  const rendered = renderWithPlugins(LoginView)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

async function submitCredentials(email: string, password: string): Promise<void> {
  await fireEvent.update(screen.getByLabelText('E-mail'), email)
  await fireEvent.update(screen.getByLabelText('Senha'), password)
  await fireEvent.click(screen.getByRole('button', { name: 'Continuar' }))
}

async function pasteCode(code: string): Promise<void> {
  const [firstBox] = await screen.findAllByRole('textbox', { name: /Código de verificação/ })
  await fireEvent.paste(firstBox!, { clipboardData: { getData: () => code } })
}

describe('LoginView', () => {
  beforeEach(() => {
    sessionStorage.clear()
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('goes straight to the code when the account has a default channel', async () => {
    // Arrange
    installFakeIdentityApi({ preferredChannel: 'email' })
    await renderLogin()

    // Act
    await submitCredentials(EMAIL, PASSWORD)

    // Assert
    expect(await screen.findByText(/Enviamos um código por e-mail/)).toBeTruthy()
    expect(screen.queryByText('Confirme que é você')).toBeNull()
  })

  it('still lets the barber switch channel after a default send', async () => {
    // Arrange
    installFakeIdentityApi({ preferredChannel: 'email' })
    await renderLogin()
    await submitCredentials(EMAIL, PASSWORD)
    await screen.findByText(/Enviamos um código por e-mail/)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Usar outro canal' }))

    // Assert
    expect(screen.getByRole('button', { name: /SMS/ })).toBeTruthy()
    expect(screen.getByRole('button', { name: /E-mail/ })).toBeTruthy()
  })

  it('asks for the channel when there is no default', async () => {
    // Arrange
    installFakeIdentityApi()
    await renderLogin()

    // Act
    await submitCredentials(EMAIL, PASSWORD)

    // Assert
    expect(await screen.findByText('Confirme que é você')).toBeTruthy()
  })

  it('validates the fields before calling the server', async () => {
    // Arrange
    const { calls } = installFakeIdentityApi()
    await renderLogin()

    // Act
    await submitCredentials('rafael@', '123')

    // Assert
    expect(screen.getByText('Informe um e-mail válido.')).toBeTruthy()
    expect(screen.getByText('A senha tem pelo menos 8 caracteres.')).toBeTruthy()
    expect(calls).toHaveLength(0)
  })

  it('shows a generic error for wrong credentials', async () => {
    // Arrange
    installFakeIdentityApi()
    await renderLogin()

    // Act
    await submitCredentials(EMAIL, 'wrong-password')

    // Assert
    expect(await screen.findByRole('alert')).toHaveProperty('textContent', expect.stringContaining('E-mail ou senha incorretos'))
  })

  it('shows a clear message when the server cannot be reached', async () => {
    // Arrange
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))
    await renderLogin()

    // Act
    await submitCredentials(EMAIL, PASSWORD)

    // Assert
    expect(await screen.findByRole('alert')).toHaveProperty('textContent', expect.stringContaining('Sem conexão com o servidor'))
  })

  it('signs in with the SMS code and goes back to the requested page', async () => {
    // Arrange
    installFakeIdentityApi()
    const { router } = await renderLogin()
    const auth = useAuthStore()
    await submitCredentials(EMAIL, PASSWORD)

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: /SMS/ }))
    expect(await screen.findByText(/Enviamos um código por SMS para \+55/)).toBeTruthy()
    await pasteCode(RIGHT_CODE)

    // Assert
    await waitForPath(router, '/painel/configuracoes')
    expect(auth.session?.user.email).toBe(EMAIL)
    expect(auth.session?.user.tenantId).toBe(3)
  })

  it('tells how many attempts are left after a wrong code', async () => {
    // Arrange
    installFakeIdentityApi()
    await renderLogin()
    const auth = useAuthStore()
    await submitCredentials(EMAIL, PASSWORD)
    await fireEvent.click(await screen.findByRole('button', { name: /E-mail/ }))

    // Act
    await pasteCode('000000')

    // Assert
    expect(await screen.findByText('Código incorreto. Você tem mais 4 tentativa(s).')).toBeTruthy()
    expect(auth.session).toBeNull()
  })
})

async function waitForPath(router: { currentRoute: { value: { fullPath: string } } }, path: string): Promise<void> {
  for (let tries = 0; tries < 200 && router.currentRoute.value.fullPath !== path; tries++) {
    await new Promise((resolve) => setTimeout(resolve, 5))
  }
  expect(router.currentRoute.value.fullPath).toBe(path)
}
