import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import PortfolioView from '../PortfolioView.vue'
import { createTestSettings, installFakeSettingsApi, publicPageBody } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

async function renderPortfolio() {
  const rendered = renderWithPlugins(PortfolioView)
  await rendered.router.push('/rafael/portfolio')
  return rendered
}

describe('PortfolioView reviews', () => {
  it('shows the carousel only when there are featured comments', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    const plain = await renderPortfolio()
    await screen.findByRole('heading', { name: /Muito prazer/ })

    // Assert
    expect(screen.queryByRole('heading', { name: 'O que dizem' })).toBeNull()
    plain.unmount()

    // Arrange
    const body = publicPageBody()
    body.featured_reviews = [{ first_name: 'Ana', rating: 5, comment: 'Corte impecável' }]
    installFakeSettingsApi(createTestSettings(), {
      'GET /public/rafael': () => ({ status: 200, body }),
    })

    // Act
    await renderPortfolio()

    // Assert
    expect(await screen.findByRole('heading', { name: 'O que dizem' })).toBeTruthy()
    expect(screen.getByText('Ana')).toBeTruthy()
    expect(screen.getByText('Corte impecável')).toBeTruthy()
  })
})
