import { describe, expect, it } from 'vitest'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import ProductsPanelView from '../ProductsPanelView.vue'
import { createTestSettings, installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

function product(id: number, name: string, position: number) {
  return {
    id,
    name,
    price_in_cents: 4500,
    description: null,
    image: null,
    is_active: true,
    position,
  }
}

describe('ProductsPanelView', () => {
  it('asks for a name and a price before saving', async () => {
    // Arrange
    const { calls } = installFakeSettingsApi(createTestSettings(), {
      'GET /products': () => ({ status: 200, body: [] }),
    })
    renderWithPlugins(ProductsPanelView)

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: 'Adicionar' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar produto' }))

    // Assert
    expect(screen.getByText('Informe o nome.')).toBeTruthy()
    expect(screen.getByText('Informe um preço maior que zero.')).toBeTruthy()
    expect(calls.some((call) => call.method === 'POST' && call.path === '/products')).toBe(false)
  })

  it('moves a product up and down', async () => {
    // Arrange
    const items = [product(1, 'Pomada', 1), product(2, 'Óleo', 2)]
    const { calls } = installFakeSettingsApi(createTestSettings(), {
      'GET /products': () => ({ status: 200, body: items }),
      'PUT /products/order': ({ body }) => {
        const ids = body?.ids as number[]
        const ordered = ids.map((id, index) => {
          const current = items.find((item) => item.id === id) ?? items[0]
          return { ...current, position: index + 1 }
        })
        return { status: 200, body: ordered }
      },
    })
    renderWithPlugins(ProductsPanelView)

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: 'Descer Pomada' }))
    await waitFor(() => {
      expect((screen.getByRole('button', { name: 'Subir Óleo' }) as HTMLButtonElement).disabled).toBe(true)
    })
    await fireEvent.click(screen.getByRole('button', { name: 'Subir Pomada' }))
    await waitFor(() => {
      expect((screen.getByRole('button', { name: 'Subir Pomada' }) as HTMLButtonElement).disabled).toBe(true)
    })

    // Assert
    const orders = calls.filter((call) => call.method === 'PUT' && call.path === '/products/order')
    expect(orders.map((call) => call.body)).toEqual([{ ids: [2, 1] }, { ids: [1, 2] }])
  })
})
