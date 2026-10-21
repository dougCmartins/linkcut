import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import PublicLinkCard from '../PublicLinkCard.vue'
import { useTenantStore } from '@/Domain/Tenant/tenant.store'
import { renderWithPlugins } from '@/utils/testRender'

function renderCard() {
  return renderWithPlugins(PublicLinkCard, {}, () => {
    useTenantStore().tenant = { id: 3, name: 'Rafael Barber', slug: 'rafael', onboarded: true }
  })
}

describe('PublicLinkCard', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('shows the public link of the barbershop and opens the page', () => {
    // Arrange & Act
    renderCard()

    // Assert
    expect(screen.getByTestId('public-link').textContent?.trim()).toBe(`${window.location.origin}/rafael`)
    expect(screen.getByRole('link', { name: 'Ver minha página' }).getAttribute('href')).toBe('/rafael')
  })

  it('copies the link to paste in the Instagram bio', async () => {
    // Arrange
    const writeText = vi.fn(async () => undefined)
    vi.stubGlobal('navigator', { ...navigator, clipboard: { writeText } })
    renderCard()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Copiar link' }))

    // Assert
    expect(writeText).toHaveBeenCalledWith(`${window.location.origin}/rafael`)
    expect(await screen.findByRole('button', { name: 'Link copiado!' })).toBeTruthy()
    expect(screen.getByText(/Agora cole no Instagram/)).toBeTruthy()
  })
})
