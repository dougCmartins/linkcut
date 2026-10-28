import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BioLinkView from '../BioLinkView.vue'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { createTestSettings, installFakeSettingsApi, publicPageBody } from '@/Domain/Settings/__tests__/fakeSettingsApi'
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
    expect(screen.getByRole('link', { name: /Agendar Horário Agora/ }).getAttribute('href')).toBe('/rafael/agendar')
    expect(screen.getByRole('link', { name: /Conheça Minha História/ }).getAttribute('href')).toBe('/rafael/portfolio')
    expect(screen.getByRole('link', { name: /WhatsApp/ }).getAttribute('href')).toContain('https://wa.me/5511999998888')
  })

  it('uses the barbershop name and bio as the page title and description (search and shared links)', async () => {
    // Arrange
    installFakeSettingsApi()
    document.head.insertAdjacentHTML('beforeend', '<meta name="description" content="">')

    // Act
    await renderBioLink()
    await screen.findByRole('heading', { name: 'Rafael Costa' })

    // Assert
    expect(document.title).toBe('Rafael Costa · linkcut')
    expect(document.querySelector('meta[name="description"]')?.getAttribute('content')).toBe('Cortes clássicos e degradês precisos.')
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

  it('shows each free link as a button with its title', async () => {
    // Arrange
    const settings = createTestSettings()
    settings.profile.links = [{ title: 'Meu site', url: 'https://example.com', icon: 'link' }]
    installFakeSettingsApi(settings)

    // Act
    await renderBioLink()

    // Assert
    const link = await screen.findByRole('link', { name: 'Meu site' })
    expect(link.getAttribute('href')).toBe('https://example.com')
    expect(link.getAttribute('target')).toBe('_blank')
  })

  it('says the page does not exist for an unknown slug', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    await renderBioLink('/nao-existe')

    // Assert
    expect(await screen.findByRole('heading', { name: 'Página não encontrada' })).toBeTruthy()
    expect(screen.queryByRole('link', { name: /Agendar Horário Agora/ })).toBeNull()
  })

  it('the main button opens the booking as its own address, so "back" closes it instead of the page', async () => {
    // Arrange
    installFakeSettingsApi()
    const { router } = await renderBioLink()

    // Act
    await fireEvent.click(await screen.findByRole('link', { name: /Agendar Horário Agora/ }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/rafael/agendar'))
    expect(router.currentRoute.value.meta.openBooking).toBe(true)
  })

  it('"Compartilhar" opens as its own history entry and closing removes it', async () => {
    // Arrange
    installFakeSettingsApi()
    const { router } = await renderBioLink()
    await fireEvent.click(await screen.findByRole('button', { name: /Compartilhar/ }))
    await vi.waitFor(() => expect(router.currentRoute.value.query.compartilhar).toBe('1'))

    // Act
    await fireEvent.click(await screen.findByRole('button', { name: 'Fechar' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/rafael'))
  })

  it('shows the stars and the average only when the page has one', async () => {
    // Arrange
    installFakeSettingsApi()

    // Act
    const hidden = await renderBioLink()
    await screen.findByRole('heading', { name: 'Rafael Costa' })

    // Assert
    expect(screen.queryByText('4,8')).toBeNull()
    expect(screen.queryByText('23 avaliações')).toBeNull()
    hidden.unmount()

    // Arrange
    const body = publicPageBody()
    body.rating = { average: 4.8, count: 23 }
    installFakeSettingsApi(createTestSettings(), {
      'GET /public/rafael': () => ({ status: 200, body }),
    })

    // Act
    await renderBioLink()

    // Assert
    expect(await screen.findByText('4,8')).toBeTruthy()
    expect(screen.getByText('23 avaliações')).toBeTruthy()
  })
})
