import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import ClientAuthView from '../ClientAuthView.vue'
import { renderWithPlugins } from '@/utils/testRender'

async function renderAt(path: string) {
  const rendered = renderWithPlugins(ClientAuthView)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('ClientAuthView', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('"Criar minha senha" swaps to the first access on the same page', async () => {
    // Arrange
    const { container, router } = await renderAt('/conta/entrar')
    const layout = container.firstElementChild

    // Act
    await fireEvent.click(screen.getByRole('link', { name: 'Criar minha senha' }))

    // Assert
    expect(await screen.findByRole('heading', { level: 1, name: 'Crie sua senha' })).toBeTruthy()
    expect(router.currentRoute.value.path).toBe('/conta/finalizar')
    expect(container.firstElementChild).toBe(layout)
    expect(screen.getByRole('link', { name: 'Entrar' }).getAttribute('href')).toBe('/conta/entrar')
  })

  it('the e-mail of the booking link fills the first access', async () => {
    // Arrange / Act
    await renderAt('/conta/finalizar?email=ana%40example.com')

    // Assert
    expect((screen.getByLabelText('E-mail') as HTMLInputElement).value).toBe('ana@example.com')
  })

  it('"Esqueci minha senha" opens the new password form and comes back', async () => {
    // Arrange
    await renderAt('/conta/entrar')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Esqueci minha senha' }))
    const resetHeading = await screen.findByRole('heading', { level: 1, name: 'Criar nova senha' })
    await fireEvent.click(screen.getByRole('button', { name: 'Voltar para o login' }))

    // Assert
    expect(resetHeading).toBeTruthy()
    expect(await screen.findByRole('heading', { level: 1, name: 'Entre na sua conta' })).toBeTruthy()
  })

  it('offers Google on the login and on the first access, and keeps an in-app return path', async () => {
    // Arrange
    const { router } = await renderAt('/conta/entrar?redirect=/conta/perfil')

    // Act
    const loginLink = screen.getByRole('link', { name: 'Continuar com Google' })
    await router.push('/conta/finalizar?redirect=/conta/perfil')
    const registerLink = await screen.findByRole('link', { name: 'Continuar com Google' })

    // Assert
    expect(returnPath(loginLink)).toBe('/conta/perfil')
    expect(loginLink.getAttribute('target')).toBeNull()
    expect(new URL(loginLink.getAttribute('href') ?? '', 'http://localhost').searchParams.get('nonce')).toMatch(/^[0-9a-f]{48}$/)
    expect(returnPath(registerLink)).toBe('/conta/perfil')
  })

  it('sends an external return path to the client home and shows a refused Google sign-in', async () => {
    // Arrange / Act
    await renderAt('/conta/entrar?redirect=https://evil.test&erro=google')

    // Assert
    expect(screen.getByRole('alert').textContent).toContain('Não foi possível entrar com o Google')
    expect(returnPath(screen.getByRole('link', { name: 'Continuar com Google' }))).toBe('/conta')
  })

  it('hides Google while the client is resetting the password', async () => {
    // Arrange
    await renderAt('/conta/entrar')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Esqueci minha senha' }))

    // Assert
    expect(await screen.findByRole('heading', { level: 1, name: 'Criar nova senha' })).toBeTruthy()
    expect(screen.queryByRole('link', { name: 'Continuar com Google' })).toBeNull()
  })
})

function returnPath(link: Element): string | null {
  return new URL(link.getAttribute('href') ?? '', 'http://localhost').searchParams.get('volta')
}
