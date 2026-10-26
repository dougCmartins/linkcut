import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import ClientAuthView from '../ClientAuthView.vue'
import { installClientGuard } from '../client.guard'
import { installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

const VISITED_SLUG_KEY = 'linkcut.visited-slug'

async function open(path: string, withGuard = false) {
  const rendered = renderWithPlugins(ClientAuthView)
  if (withGuard) installClientGuard(rendered.router)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('client login on the barbershop page', () => {
  beforeEach(() => localStorage.clear())

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('/rafael/login shows the barbershop and the client login form', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    await open('/rafael/login')

    // Assert
    expect(await screen.findByText('Rafael Costa')).toBeTruthy()
    expect(screen.getByRole('heading', { level: 1, name: 'Entre na sua conta' })).toBeTruthy()
    expect(localStorage.getItem(VISITED_SLUG_KEY)).toBe('rafael')
  })

  it('a client without session goes to the login of the last barbershop opened on this device', async () => {
    // Arrange
    installFakeSettingsApi()
    localStorage.setItem(VISITED_SLUG_KEY, 'rafael')

    // Act
    const { router } = await open('/conta', true)

    // Assert
    expect(router.currentRoute.value.fullPath).toBe('/rafael/login?redirect=/conta')
  })

  it('without any barbershop opened here, the client signs in on /conta/entrar', async () => {
    // Arrange / Act
    const { router } = await open('/conta', true)

    // Assert
    expect(router.currentRoute.value.fullPath).toBe('/conta/entrar?redirect=/conta')
  })

  it('/conta/entrar sends on to the barbershop login and keeps the notice (e.g. Google refused)', async () => {
    // Arrange
    installFakeSettingsApi()
    localStorage.setItem(VISITED_SLUG_KEY, 'rafael')

    // Act
    const { router } = await open('/conta/entrar?erro=google')

    // Assert
    expect(router.currentRoute.value.fullPath).toBe('/rafael/login?erro=google')
    expect(await screen.findByText(/Não foi possível entrar com o Google/)).toBeTruthy()
  })

  it('the old barber address /rafael/entrar opens /rafael/panel/login', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    const { router } = await open('/rafael/entrar')

    // Assert
    expect(router.currentRoute.value.fullPath).toBe('/rafael/panel/login')
  })
})
