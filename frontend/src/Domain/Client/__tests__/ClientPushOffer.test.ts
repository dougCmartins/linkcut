import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import ClientPushOffer from '../ClientPushOffer.vue'
import { installFakePushBrowser, removeFakePushBrowser } from '@/Domain/Notification/__tests__/fakePushBrowser'
import { installFakeApi } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const PUSH_URL = '/api/public/client-push/11?expires=1&signature=abc'

function installFakeClientPushApi() {
  return installFakeApi({
    'GET /public/push-key': () => ({ status: 200, body: { public_key: 'BServerKey' } }),
    'POST /public/client-push/11': () => ({ status: 204 }),
    'POST /client/push-subscriptions': () => ({ status: 204 }),
  })
}

describe('ClientPushOffer', () => {
  afterEach(() => {
    removeFakePushBrowser()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it("right after booking, signs this device up with the booking's signed link", async () => {
    // Arrange
    installFakePushBrowser({ permission: 'granted' })
    const { calls } = installFakeClientPushApi()
    renderWithPlugins(ClientPushOffer, { props: { pushUrl: PUSH_URL } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Avisar neste aparelho' }))

    // Assert
    expect(await screen.findByText(/Você vai receber os avisos neste aparelho/)).toBeTruthy()
    const saved = calls.find((call) => call.method === 'POST')
    expect(saved?.path).toBe('/public/client-push/11?expires=1&signature=abc')
    expect(saved?.body).toEqual({ endpoint: 'https://push.test/device-1', keys: { p256dh: 'BBrowserKey', auth: 'AuthSecret' } })
  })

  it('in "Minha conta" (no link), uses the client session', async () => {
    // Arrange
    installFakePushBrowser({ permission: 'granted' })
    const { calls } = installFakeClientPushApi()
    renderWithPlugins(ClientPushOffer)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Avisar neste aparelho' }))

    // Assert
    await screen.findByText(/Você vai receber os avisos neste aparelho/)
    expect(calls.find((call) => call.method === 'POST')?.path).toBe('/client/push-subscriptions')
  })

  it('when the browser blocks notifications, says the e-mails still arrive and sends nothing', async () => {
    // Arrange
    installFakePushBrowser({ permission: 'denied' })
    const { calls } = installFakeClientPushApi()
    renderWithPlugins(ClientPushOffer, { props: { pushUrl: PUSH_URL } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Avisar neste aparelho' }))

    // Assert
    expect(await screen.findByText(/Você ainda recebe tudo por e-mail/)).toBeTruthy()
    expect(calls.filter((call) => call.method === 'POST')).toHaveLength(0)
  })
})
