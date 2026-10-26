import { afterEach, describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import BookingSuccess from '../BookingSuccess.vue'
import { renderWithPlugins } from '@/utils/testRender'

describe('BookingSuccess', () => {
  afterEach(() => vi.unstubAllEnvs())

  it('offers Google sign-in back to the client area when it is on', () => {
    // Arrange
    vi.stubEnv('VITE_GOOGLE_LOGIN', 'true')

    // Act
    renderWithPlugins(BookingSuccess, { props: { summary: 'Corte, amanhã às 10:00' } })
    const link = screen.getByRole('link', { name: 'Continuar com Google' })

    // Assert
    expect(new URL(link.getAttribute('href') ?? '', 'http://localhost').searchParams.get('volta')).toBe('/conta')
    expect(link.getAttribute('target')).toBeNull()
  })

  it('shows no Google button while it is off', () => {
    // Arrange
    vi.stubEnv('VITE_GOOGLE_LOGIN', '')

    // Act
    renderWithPlugins(BookingSuccess, { props: { summary: 'Corte, amanhã às 10:00' } })

    // Assert
    expect(screen.queryByRole('link', { name: 'Continuar com Google' })).toBeNull()
  })
})
