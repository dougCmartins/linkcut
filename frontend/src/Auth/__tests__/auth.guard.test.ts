import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { installAuthGuard, safeRedirect } from '../auth.guard'
import { useAuthStore } from '../auth.store'
import { routes } from '@/router'

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
    user: { name: 'Rafael Costa', email: 'rafael@linkcut.app' },
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
    await router.push('/portfolio')

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
