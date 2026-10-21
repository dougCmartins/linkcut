import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { installAuthGuard, safeRedirect } from '../auth.guard'
import { useAuthStore } from '../auth.store'
import { routes } from '@/router'
import { installFakeApi } from '@/utils/testFetch'

function createTestRouter() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  installAuthGuard(router)
  return router
}

function signIn(): void {
  const now = Date.now()
  useAuthStore().session = {
    accessToken: 'access',
    refreshToken: 'refresh',
    accessExpiresAt: now + 60_000,
    refreshExpiresAt: now + 120_000,
    user: { id: 7, tenantId: 3, name: 'Rafael Costa', email: 'rafael@linkcut.app', phoneMasked: null, role: 'owner', twoFactorChannel: null },
  }
}

describe('auth guard', () => {
  beforeEach(() => {
    sessionStorage.clear()
    setActivePinia(createPinia())
  })

  it('sends a visitor without session from the dashboard to the login', async () => {
    // Arrange
    const router = createTestRouter()

    // Act
    await router.push('/painel/configuracoes?aba=agenda')

    // Assert
    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/painel/configuracoes?aba=agenda')
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('sends a new barbershop to the setup wizard until it is done', async () => {
    // Arrange
    installFakeApi({ 'GET /tenant': () => ({ status: 200, body: { id: 3, name: 'Nova', slug: 'nova', onboarded: false } }) })
    const router = createTestRouter()
    signIn()

    // Act
    await router.push('/painel/despesas')

    // Assert
    expect(router.currentRoute.value.name).toBe('onboarding')
  })

  it('a barbershop already set up never sees the wizard again', async () => {
    // Arrange
    installFakeApi({ 'GET /tenant': () => ({ status: 200, body: { id: 3, name: 'Rafael', slug: 'rafael', onboarded: true } }) })
    const router = createTestRouter()
    signIn()

    // Act
    await router.push('/painel/boas-vindas')

    // Assert
    expect(router.currentRoute.value.path).toBe('/painel')
  })

  it('lets a signed-in barber open the dashboard', async () => {
    // Arrange
    const router = createTestRouter()
    signIn()

    // Act
    await router.push('/painel')

    // Assert
    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('keeps the public pages open to everyone', async () => {
    // Arrange
    const router = createTestRouter()

    // Act
    await router.push('/rafael/portfolio')

    // Assert
    expect(router.currentRoute.value.name).toBe('portfolio')
  })

  it('sends a signed-in barber away from the login page', async () => {
    // Arrange
    const router = createTestRouter()
    signIn()

    // Act
    await router.push('/entrar')

    // Assert
    expect(router.currentRoute.value.path).toBe('/painel')
  })

  it('blocks a session whose refresh token also expired', async () => {
    // Arrange
    const router = createTestRouter()
    signIn()
    const auth = useAuthStore()
    auth.session = { ...auth.session!, accessExpiresAt: Date.now() - 1, refreshExpiresAt: Date.now() - 1 }

    // Act
    await router.push('/painel')

    // Assert
    expect(router.currentRoute.value.name).toBe('login')
    expect(auth.session).toBeNull()
  })
})

describe('safeRedirect', () => {
  it('only follows paths inside the app', () => {
    // Arrange
    const targets = ['/painel/configuracoes', '//evil.com', 'https://evil.com', undefined]

    // Act
    const results = targets.map(safeRedirect)

    // Assert
    expect(results).toEqual(['/painel/configuracoes', '/painel', '/painel', '/painel'])
  })
})
