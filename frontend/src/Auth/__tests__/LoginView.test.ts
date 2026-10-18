import { beforeEach, describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import LoginView from '../LoginView.vue'
import { useAuthStore } from '../auth.store'
import { DEMO_ACCOUNT } from '../auth.api'
import { renderWithPlugins } from '@/utils/testRender'

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
  })

  it('validates the fields before calling the server', async () => {
    // Arrange
    await renderLogin()

    // Act
    await submitCredentials('rafael@', '123')

    // Assert
    expect(screen.getByText('Informe um e-mail válido.')).toBeTruthy()
    expect(screen.getByText('A senha tem pelo menos 8 caracteres.')).toBeTruthy()
    expect(useAuthStore().challenge).toBeNull()
  })

  it('shows a generic error for wrong credentials', async () => {
    // Arrange
    await renderLogin()

    // Act
    await submitCredentials(DEMO_ACCOUNT.email, 'wrong-password')

    // Assert
    expect(await screen.findByRole('alert')).toHaveProperty('textContent', expect.stringContaining('E-mail ou senha incorretos'))
  })

  it('signs in with the 2FA code and goes back to the requested page', async () => {
    // Arrange
    const { router } = await renderLogin()
    const auth = useAuthStore()
    await submitCredentials(DEMO_ACCOUNT.email, DEMO_ACCOUNT.password)

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: /WhatsApp/ }))
    expect(await screen.findByText(/Enviamos um código pelo WhatsApp/)).toBeTruthy()
    await pasteCode(auth.delivery?.devCode ?? '')
    await new Promise((resolve) => setTimeout(resolve, 0))
    await router.isReady()

    // Assert
    expect(auth.session?.user.email).toBe(DEMO_ACCOUNT.email)
    await waitForPath(router, '/painel/configuracoes')
  })

  it('tells how many attempts are left after a wrong code', async () => {
    // Arrange
    await renderLogin()
    const auth = useAuthStore()
    await submitCredentials(DEMO_ACCOUNT.email, DEMO_ACCOUNT.password)
    await fireEvent.click(await screen.findByRole('button', { name: /E-mail/ }))
    const wrong = auth.delivery?.devCode === '000000' ? '111111' : '000000'

    // Act
    await pasteCode(wrong)

    // Assert
    expect(await screen.findByText('Código incorreto. Você tem mais 4 tentativa(s).')).toBeTruthy()
    expect(auth.session).toBeNull()
  })
})

async function waitForPath(router: { currentRoute: { value: { fullPath: string } } }, path: string): Promise<void> {
  for (let tries = 0; tries < 20 && router.currentRoute.value.fullPath !== path; tries++) {
    await new Promise((resolve) => setTimeout(resolve, 5))
  }
  expect(router.currentRoute.value.fullPath).toBe(path)
}
