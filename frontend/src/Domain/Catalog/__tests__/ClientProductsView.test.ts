import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import ClientProductsView from '../ClientProductsView.vue'
import { clientSessionBody, installFakeClientApi } from '@/Domain/Client/__tests__/fakeClientApi'
import { useClientStore } from '@/Domain/Client/client.store'
import { renderWithPlugins } from '@/utils/testRender'

async function renderProducts(path = '/navalha/account/products') {
  const rendered = renderWithPlugins(ClientProductsView, {}, () => {
    const now = Date.now()
    const body = clientSessionBody()
    useClientStore().session = {
      accessToken: body.access_token,
      refreshToken: body.refresh_token,
      accessExpiresAt: now + 60_000,
      refreshExpiresAt: now + 120_000,
      client: { id: 11, name: 'Ana Souza', email: 'ana@example.com', phone: '11912345678', hasAccount: true, emailsEnabled: true },
    }
  })
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('ClientProductsView', () => {
  it('lists the products and opens WhatsApp with the product name', async () => {
    // Arrange
    installFakeClientApi({
      'GET /client/barbershops/navalha/products': () => ({
        status: 200,
        body: [{ id: 1, name: 'Pomada', price_in_cents: 4500, description: 'Efeito matte', image_url: 'https://cdn.test/pomada.webp' }],
      }),
    })

    // Act
    await renderProducts()
    const link = await screen.findByRole('link', { name: 'Tenho interesse' })

    // Assert
    expect(screen.getByRole('heading', { name: 'Pomada' })).toBeTruthy()
    expect(screen.getByText(/45,00/)).toBeTruthy()
    expect(screen.getByText('Efeito matte')).toBeTruthy()
    expect(link.getAttribute('href')).toContain('https://wa.me/5511999998888')
    expect(link.getAttribute('href')).toContain(encodeURIComponent('Pomada'))
    expect(screen.getByRole('img', { name: 'Pomada' }).getAttribute('loading')).toBe('lazy')
  })
})
