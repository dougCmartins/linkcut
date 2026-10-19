import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BioLinkView from '../BioLinkView.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { createTestSettings, installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { ThemePalette } from '@/types/theme'
import { renderWithPlugins } from '@/utils/testRender'

async function renderBioLink(path = '/rafael') {
  const rendered = renderWithPlugins(BioLinkView)
  await rendered.router.push(path)
  return rendered
}

describe('BioLinkView', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('shows the barbershop of the slug with the three main actions', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    await renderBioLink()

    // Assert
    expect(await screen.findByRole('heading', { name: 'Rafael Costa' })).toBeTruthy()
    expect(screen.getByRole('button', { name: /Agendar Horário Agora/ })).toBeTruthy()
    expect(screen.getByRole('link', { name: /Conheça Minha História/ }).getAttribute('href')).toBe('/rafael/portfolio')
    expect(screen.getByRole('link', { name: /WhatsApp/ }).getAttribute('href')).toContain('https://wa.me/5511999998888')
  })

  it('reads the public API without a session and loads the theme of the barbershop', async () => {
    // Arrange
    const settings = createTestSettings()
    settings.theme.palette = ThemePalette.Ocean
    const { calls } = installFakeSettingsApi(settings)

    // Act
    await renderBioLink()
    await screen.findByRole('heading', { name: 'Rafael Costa' })

    // Assert
    expect(calls.map((call) => call.path)).toEqual(['/public/rafael', '/public/rafael/booking-options'])
    expect(calls.every((call) => !call.headers.Authorization)).toBe(true)
    expect(usePublicPageStore().theme.palette).toBe(ThemePalette.Ocean)
    expect(usePublicPageStore().bookingOptions?.services[0]?.costInCents).toBeUndefined()
  })

  it('shows only the social networks that have a link', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    await renderBioLink()

    // Assert
    const socials = await screen.findByRole('navigation', { name: 'Redes sociais' })
    expect(socials.querySelectorAll('a')).toHaveLength(1)
  })

  it('says the page does not exist for an unknown slug', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    await renderBioLink('/nao-existe')

    // Assert
    expect(await screen.findByRole('heading', { name: 'Página não encontrada' })).toBeTruthy()
    expect(screen.queryByRole('button', { name: /Agendar Horário Agora/ })).toBeNull()
  })

  it('opens the booking modal from the main button', async () => {
    // Arrange
    installFakeSettingsApi()
    await renderBioLink()
    const store = useBookingStore()

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: /Agendar Horário Agora/ }))

    // Assert
    expect(store.isModalOpen).toBe(true)
  })
})
