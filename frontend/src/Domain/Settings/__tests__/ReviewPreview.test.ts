import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import LinkPreview from '../LinkPreview.vue'
import PortfolioPreview from '../PortfolioPreview.vue'
import { createTestSettings, installFakeSettingsApi } from './fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

describe('review previews', () => {
  it('shows the average on the link preview only when a rating is passed', () => {
    // Arrange
    const settings = createTestSettings()
    const plain = renderWithPlugins(LinkPreview, {
      props: { profile: settings.profile, avatarUrl: '', backgroundUrl: '' },
    })

    // Assert
    expect(screen.queryByText('4,8')).toBeNull()
    plain.unmount()

    // Act
    renderWithPlugins(LinkPreview, {
      props: { profile: settings.profile, avatarUrl: '', backgroundUrl: '', rating: { average: 4.8, count: 23 } },
    })

    // Assert
    expect(screen.getByText('4,8')).toBeTruthy()
    expect(screen.getByText('23 avaliações')).toBeTruthy()
  })

  it('shows featured comments on the portfolio phone only when the option is on', async () => {
    // Arrange
    installFakeSettingsApi(createTestSettings(), {
      'GET /reviews': () => ({
        status: 200,
        body: [
          {
            id: 1,
            booking_request_id: 1,
            rating: 5,
            comment: 'Corte impecável',
            first_name: 'Ana',
            date: '2026-10-12',
            hidden_at: null,
            featured_position: 1,
          },
        ],
      }),
    })
    const settings = createTestSettings()
    const base = { name: settings.profile.name, coverUrl: '', photoUrls: [] as string[] }

    // Act
    const off = renderWithPlugins(PortfolioPreview, { props: { ...base, portfolio: settings.portfolio } })
    await screen.findByText(/Muito prazer/)

    // Assert
    expect(screen.queryByText('Corte impecável')).toBeNull()
    off.unmount()

    // Act
    settings.portfolio.showReviews = true
    renderWithPlugins(PortfolioPreview, { props: { ...base, portfolio: settings.portfolio } })

    // Assert
    expect(await screen.findByText('Corte impecável')).toBeTruthy()
    expect(screen.getByText('Ana')).toBeTruthy()
  })
})
