import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BioLinkView from '../BioLinkView.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { renderWithPlugins } from '@/utils/testRender'
import { barberProfile } from '@/mocks/barber'

describe('BioLinkView', () => {
  it('shows the barber name and the three main actions', () => {
    // Arrange & Act
    renderWithPlugins(BioLinkView)

    // Assert
    expect(screen.getByRole('heading', { name: barberProfile.name })).toBeTruthy()
    expect(screen.getByRole('button', { name: /Agendar Horário Agora/ })).toBeTruthy()
    expect(screen.getByRole('link', { name: /Conheça Minha História/ }).getAttribute('href')).toBe('/portfolio')
    expect(screen.getByRole('link', { name: /WhatsApp/ }).getAttribute('href')).toContain(
      `https://wa.me/${barberProfile.whatsappNumber}`,
    )
  })

  it('opens the booking modal from the main button', async () => {
    // Arrange
    renderWithPlugins(BioLinkView)
    const store = useBookingStore()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: /Agendar Horário Agora/ }))

    // Assert
    expect(store.isModalOpen).toBe(true)
  })
})
