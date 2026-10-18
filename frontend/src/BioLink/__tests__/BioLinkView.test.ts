import { beforeEach, describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BioLinkView from '../BioLinkView.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { useSettingsStore } from '@/Settings/settings.store'
import { SettingsSection } from '@/Settings/settings.model'
import { renderWithPlugins } from '@/utils/testRender'

describe('BioLinkView', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('shows the barber name and the three main actions', () => {
    // Arrange & Act
    renderWithPlugins(BioLinkView)

    // Assert
    expect(screen.getByRole('heading', { name: 'Rafael Costa' })).toBeTruthy()
    expect(screen.getByRole('button', { name: /Agendar Horário Agora/ })).toBeTruthy()
    expect(screen.getByRole('link', { name: /Conheça Minha História/ }).getAttribute('href')).toBe('/portfolio')
    expect(screen.getByRole('link', { name: /WhatsApp/ }).getAttribute('href')).toContain('https://wa.me/5511999998888')
  })

  it('shows what the barber saved in the settings', async () => {
    // Arrange
    const { rerender } = renderWithPlugins(BioLinkView)
    const settings = useSettingsStore()
    settings.drafts.profile.name = 'Diego Souza'
    settings.drafts.profile.whatsappNumber = '(21) 98888-7777'
    settings.drafts.profile.socials = settings.drafts.profile.socials.map((social) => ({ ...social, url: '' }))

    // Act
    settings.save(SettingsSection.Profile)
    await rerender({})

    // Assert
    expect(screen.getByRole('heading', { name: 'Diego Souza' })).toBeTruthy()
    expect(screen.getByRole('link', { name: /WhatsApp/ }).getAttribute('href')).toContain('https://wa.me/5521988887777')
    expect(screen.queryByRole('navigation', { name: 'Redes sociais' })).toBeNull()
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
