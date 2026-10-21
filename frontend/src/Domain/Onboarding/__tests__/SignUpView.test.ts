import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import SignUpView from '../SignUpView.vue'
import { SIGN_UP_CODE, installFakeOnboardingApi } from './fakeOnboardingApi'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { renderWithPlugins } from '@/utils/testRender'

async function renderSignUp() {
  const rendered = renderWithPlugins(SignUpView)
  await rendered.router.push('/cadastrar')
  await rendered.router.isReady()
  return rendered
}

async function fillAccountAndCode(code = SIGN_UP_CODE): Promise<void> {
  await fireEvent.update(screen.getByLabelText('Seu nome'), 'Marcos Lima')
  await fireEvent.update(screen.getByLabelText('E-mail'), 'marcos@example.com')
  await fireEvent.update(screen.getByLabelText('Senha'), 'segredo123')
  await fireEvent.click(screen.getByRole('button', { name: 'Continuar' }))
  const [firstBox] = await screen.findAllByRole('textbox', { name: /Código de verificação/ })
  await fireEvent.paste(firstBox!, { clipboardData: { getData: () => code } })
}

describe('SignUpView', () => {
  beforeEach(() => {
    sessionStorage.clear()
    vi.useFakeTimers({ shouldAdvanceTime: true })
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('creates the account with the e-mail code and opens the setup wizard signed in', async () => {
    // Arrange
    const { calls } = installFakeOnboardingApi()
    const { router } = await renderSignUp()
    await fillAccountAndCode()

    // Act
    await fireEvent.update(await screen.findByLabelText('Nome da barbearia'), 'Barbearia do Marcos')
    expect((screen.getByLabelText('Link da sua página') as HTMLInputElement).value).toBe('barbearia-do-marcos')
    await vi.advanceTimersByTimeAsync(500)
    await screen.findByText('Este link está livre.')
    await fireEvent.click(screen.getByRole('button', { name: 'Criar minha página' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('onboarding'))
    expect(calls.find((call) => call.path === '/sign-up')?.body).toEqual({
      name: 'Marcos Lima',
      email: 'marcos@example.com',
      password: 'segredo123',
      code: SIGN_UP_CODE,
      barbershop_name: 'Barbearia do Marcos',
      slug: 'barbearia-do-marcos',
    })
    expect(useAuthStore().session?.accessToken).toBe('new-access')
  })

  it('a taken link offers a free one', async () => {
    // Arrange
    installFakeOnboardingApi()
    await renderSignUp()
    await fillAccountAndCode()

    // Act
    await fireEvent.update(await screen.findByLabelText('Nome da barbearia'), 'Navalha')
    await vi.advanceTimersByTimeAsync(500)
    await fireEvent.click(await screen.findByRole('button', { name: 'Usar navalha-2' }))
    await vi.advanceTimersByTimeAsync(500)

    // Assert
    expect(await screen.findByText('Este link está livre.')).toBeTruthy()
    expect((screen.getByLabelText('Link da sua página') as HTMLInputElement).value).toBe('navalha-2')
  })

  it('a reserved link is refused without asking the API', async () => {
    // Arrange
    const { calls } = installFakeOnboardingApi()
    await renderSignUp()
    await fillAccountAndCode()

    // Act
    await fireEvent.update(await screen.findByLabelText('Link da sua página'), 'painel')
    await vi.advanceTimersByTimeAsync(500)

    // Assert
    expect(screen.getByText('Este link já está em uso.')).toBeTruthy()
    expect(calls.some((call) => call.path.startsWith('/sign-up/slug'))).toBe(false)
    expect((screen.getByRole('button', { name: 'Criar minha página' }) as HTMLButtonElement).disabled).toBe(true)
  })

  it('a wrong code goes back to the code step with the attempts left', async () => {
    // Arrange
    installFakeOnboardingApi()
    await renderSignUp()
    await fillAccountAndCode('000000')
    await fireEvent.update(await screen.findByLabelText('Nome da barbearia'), 'Barbearia do Marcos')
    await vi.advanceTimersByTimeAsync(500)
    await screen.findByText('Este link está livre.')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Criar minha página' }))

    // Assert
    expect(await screen.findByText('Código incorreto. Você tem mais 4 tentativa(s).')).toBeTruthy()
    expect(screen.getByText('Confirme seu e-mail')).toBeTruthy()
    expect(useAuthStore().session).toBeNull()
  })
})
