import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import PushPrompt from '../PushPrompt.vue'
import PushSwitch from '../PushSwitch.vue'
import { installFakePushBrowser, removeFakePushBrowser } from './fakePushBrowser'
import { installFakeApi } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const PUBLIC_KEY = 'BNoRP7V2noFIQdPQHTU0c7lmNbx1Yl0wF8lE7q-Qn9Vt2yH4xk3tLq8oJ6m1rS0aP5vW2zE9cK7uY4iO3gD1fBw'

describe('push notices on screen', () => {
  beforeEach(() => {
    localStorage.clear()
    installFakeApi({
      'GET /notifications/push/public-key': () => ({ status: 200, body: { public_key: PUBLIC_KEY } }),
      'POST /notifications/push/subscriptions': () => ({ status: 204 }),
      'DELETE /notifications/push/subscriptions': () => ({ status: 204 }),
    })
  })

  afterEach(() => {
    removeFakePushBrowser()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('the dashboard invites to turn on the notices and hides once they are on', async () => {
    // Arrange
    installFakePushBrowser()
    renderWithPlugins(PushPrompt)

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: 'Ativar avisos' }))

    // Assert
    await vi.waitFor(() => expect(screen.queryByText('Saiba na hora quando chegar um pedido')).toBeNull())
  })

  it('"Agora não" hides the invitation on this device', async () => {
    // Arrange
    installFakePushBrowser()
    renderWithPlugins(PushPrompt)

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: 'Agora não' }))

    // Assert
    expect(screen.queryByText('Saiba na hora quando chegar um pedido')).toBeNull()
    expect(localStorage.getItem('linkcut.pushPromptDismissed')).toBe('1')
  })

  it('the settings switch turns the notices on and off', async () => {
    // Arrange
    const browser = installFakePushBrowser()
    renderWithPlugins(PushSwitch)
    const toggle = await screen.findByRole('switch', { name: 'Avisos de novos pedidos neste aparelho' })

    // Act
    await fireEvent.click(toggle)
    await vi.waitFor(() => expect(toggle.getAttribute('aria-checked')).toBe('true'))
    await fireEvent.click(toggle)

    // Assert
    await vi.waitFor(() => expect(toggle.getAttribute('aria-checked')).toBe('false'))
    expect(browser.current()).toBeNull()
  })

  it('explains when the browser blocked the notifications', async () => {
    // Arrange
    installFakePushBrowser({ permission: 'denied' })
    renderWithPlugins(PushSwitch)

    // Act
    await fireEvent.click(await screen.findByRole('switch'))

    // Assert
    expect(await screen.findByText(/As notificações estão bloqueadas para o linkcut/)).toBeTruthy()
  })
})
