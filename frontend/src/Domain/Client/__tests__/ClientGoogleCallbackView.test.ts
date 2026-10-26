import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import ClientGoogleCallbackView from '../ClientGoogleCallbackView.vue'
import { clientSessionBody } from './fakeClientApi'
import { envelope, installFakeApi } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'
import { useClientStore } from '../client.store'

async function open(path: string) {
  const rendered = renderWithPlugins(ClientGoogleCallbackView)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('ClientGoogleCallbackView', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })
  it('exchanges the code with the nonce this browser kept, and opens the return path', async () => {
    // Arrange
    localStorage.setItem('linkcut.google-nonce', 'nonce-started-here')
    const { calls } = installFakeApi({
      'POST /client/auth/google/exchange': () => ({ status: 200, body: clientSessionBody() }),
    })

    // Act
    const { router } = await open('/conta/entrar/google?codigo=once&volta=/conta/perfil')

    // Assert
    await waitFor(() => expect(router.currentRoute.value.path).toBe('/conta/perfil'))
    expect(calls.find((call) => call.method === 'POST')?.body).toEqual({ code: 'once', nonce: 'nonce-started-here' })
    expect(localStorage.getItem('linkcut.google-nonce')).toBeNull()
    expect(useClientStore().isSignedIn).toBe(true)
    expect(screen.getByRole('status').textContent).toContain('Entrando com Google…')
  })

  it('shows the error when the code is invalid', async () => {
    // Arrange
    installFakeApi({
      'POST /client/auth/google/exchange': () => envelope('GOOGLE_CODE_INVALID', 422),
    })

    // Act
    const { router } = await open('/conta/entrar/google?codigo=used&volta=/conta')

    // Assert
    expect((await screen.findByRole('alert')).textContent).toContain('Esse acesso do Google expirou')
    expect(router.currentRoute.value.path).toBe('/conta/entrar/google')
    expect(useClientStore().isSignedIn).toBe(false)
  })

  it('does not follow an external return path', async () => {
    // Arrange
    installFakeApi({
      'POST /client/auth/google/exchange': () => ({ status: 200, body: clientSessionBody() }),
    })

    // Act
    const { router } = await open(`/conta/entrar/google?codigo=once&volta=${encodeURIComponent('https://evil.test')}`)

    // Assert
    await waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/conta'))
  })

  it('shows an error when Google sent no code', async () => {
    // Arrange
    const { calls } = installFakeApi({})

    // Act
    await open('/conta/entrar/google?volta=/conta')

    // Assert
    expect((await screen.findByRole('alert')).textContent).toContain('Não recebemos o acesso do Google')
    expect(calls).toHaveLength(0)
  })
})
