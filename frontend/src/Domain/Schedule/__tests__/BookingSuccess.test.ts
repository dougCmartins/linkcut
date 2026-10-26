import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import BookingSuccess from '../BookingSuccess.vue'
import { renderWithPlugins } from '@/utils/testRender'

describe('BookingSuccess', () => {
  it('offers Google sign-in back to the client area', () => {
    // Arrange / Act
    renderWithPlugins(BookingSuccess, { props: { summary: 'Corte, amanhã às 10:00' } })
    const link = screen.getByRole('link', { name: 'Continuar com Google' })

    // Assert
    expect(new URL(link.getAttribute('href') ?? '', 'http://localhost').searchParams.get('volta')).toBe('/conta')
    expect(link.getAttribute('target')).toBeNull()
  })
})
