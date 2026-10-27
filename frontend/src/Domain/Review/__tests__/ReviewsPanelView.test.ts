import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import ReviewsPanelView from '../ReviewsPanelView.vue'
import { createTestSettings, installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

function review(id: number, comment: string) {
  return {
    id,
    booking_request_id: id,
    rating: 5,
    comment,
    first_name: 'Ana',
    date: '2026-10-12',
    hidden_at: null,
    featured_position: null,
  }
}

describe('ReviewsPanelView', () => {
  it('keeps at most three comments on the page', async () => {
    // Arrange
    installFakeSettingsApi(createTestSettings(), {
      'GET /reviews': () => ({
        status: 200,
        body: [review(1, 'Um'), review(2, 'Dois'), review(3, 'Três'), review(4, 'Quatro')],
      }),
      'PUT /reviews/featured': () => ({ status: 200, body: [] }),
    })
    renderWithPlugins(ReviewsPanelView)

    // Act
    const buttons = await screen.findAllByRole('button', { name: 'Mostrar na página' })
    expect(buttons).toHaveLength(4)
    await fireEvent.click(buttons[0]!)
    await fireEvent.click(buttons[1]!)
    await fireEvent.click(buttons[2]!)

    // Assert
    const left = await screen.findByRole('button', { name: 'Mostrar na página' })
    expect(left.hasAttribute('disabled')).toBe(true)
    expect(screen.getAllByRole('button', { name: 'Na página' })).toHaveLength(3)
  })
})
