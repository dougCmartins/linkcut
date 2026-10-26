import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BarberAuthView from '../BarberAuthView.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { installFakeApi } from '@/utils/testFetch'

async function renderAt(path: string) {
  const rendered = renderWithPlugins(BarberAuthView)
  await rendered.router.push(path)
  await rendered.router.isReady()
  // The panel of each mode is loaded on demand.
  await vi.dynamicImportSettled()
  return rendered
}

const photoAlt = (container: Element) => container.querySelector('aside img')?.getAttribute('alt')

describe('BarberAuthView', () => {
  beforeEach(() => {
    sessionStorage.clear()
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('opens on "Criar conta" with "Entrar" below', async () => {
    // Arrange / Act
    const { container } = await renderAt('/')

    // Assert
    expect(screen.getByRole('heading', { level: 1, name: 'Crie sua página grátis' })).toBeTruthy()
    expect(screen.getByText('Já tem conta?')).toBeTruthy()
    expect(screen.getByRole('link', { name: 'Entrar' }).getAttribute('href')).toBe('/entrar')
    expect(photoAlt(container)).toMatch(/Fachada/)
  })

  it('"Entrar" swaps the form and the photo on the same page', async () => {
    // Arrange
    const { container, router } = await renderAt('/')
    const layout = container.firstElementChild

    // Act
    await fireEvent.click(screen.getByRole('link', { name: 'Entrar' }))

    // Assert
    expect(await screen.findByRole('heading', { level: 1, name: 'Entre no seu painel' })).toBeTruthy()
    expect(router.currentRoute.value.path).toBe('/entrar')
    expect(photoAlt(container)).toMatch(/Interior/)
    expect(container.firstElementChild).toBe(layout)
    expect(screen.getByRole('link', { name: 'Criar minha página grátis' }).getAttribute('href')).toBe('/cadastrar')
  })

  it('going back returns to "Criar conta"', async () => {
    // Arrange
    const { router } = await renderAt('/')
    await fireEvent.click(screen.getByRole('link', { name: 'Entrar' }))
    await screen.findByRole('heading', { level: 1, name: 'Entre no seu painel' })

    // Act
    router.back()

    // Assert
    expect(await screen.findByRole('heading', { level: 1, name: 'Crie sua página grátis' })).toBeTruthy()
  })

  it('keeps the page asked before signing in when switching', async () => {
    // Arrange
    await renderAt('/entrar?redirect=/painel/historico')

    // Act
    const signUpLink = screen.getByRole('link', { name: 'Criar minha página grátis' })

    // Assert
    expect(signUpLink.getAttribute('href')).toBe('/cadastrar?redirect=/painel/historico')
  })

  it('"Entrar" e-mails the sign-in link of the barber\'s own page (same answer for any e-mail)', async () => {
    // Arrange
    const { calls } = installFakeApi({ 'POST /login/link': () => ({ status: 202, body: { sent: true } }) })
    await renderAt('/entrar')

    // Act
    await fireEvent.update(screen.getByLabelText('E-mail'), 'rafael@linkcut.app')
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar meu link de entrada' }))

    // Assert
    expect(await screen.findByText(/Se rafael@linkcut.app tiver uma barbearia/)).toBeTruthy()
    expect(calls.at(-1)).toMatchObject({ method: 'POST', path: '/login/link', body: { email: 'rafael@linkcut.app' } })
  })

  it('offers the page this device signed in from last', async () => {
    // Arrange
    localStorage.setItem('linkcut.last-slug', 'rafael')

    // Act
    await renderAt('/entrar')

    // Assert
    expect(screen.getByRole('link', { name: /Continuar em .*\/rafael/ }).getAttribute('href')).toBe('/rafael/panel/login')
  })
})
