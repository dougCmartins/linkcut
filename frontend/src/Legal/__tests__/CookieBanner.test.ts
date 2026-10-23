import { beforeEach, describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import CookieBanner from '../CookieBanner.vue'
import PrivacyLinks from '../PrivacyLinks.vue'
import { CONSENT_KEY, CONSENT_VERSION, readConsent } from '../consent'
import { useConsentStore } from '../consent.store'
import { renderWithPlugins } from '@/utils/testRender'


const stored = () => JSON.parse(localStorage.getItem(CONSENT_KEY) ?? 'null')

describe('privacy notice (LGPD)', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('asks on the first visit with "accept" and "reject" side by side', () => {
    // Arrange & Act
    renderWithPlugins(CookieBanner)

    // Assert
    expect(screen.getByRole('region', { name: 'Aviso de privacidade' })).toBeTruthy()
    expect(screen.getByRole('button', { name: 'Aceitar tudo' })).toBeTruthy()
    expect(screen.getByRole('button', { name: 'Rejeitar opcionais' })).toBeTruthy()
    expect(screen.getByRole('link', { name: 'Política de Privacidade' }).getAttribute('href')).toBe('/privacidade')
  })

  it('"Rejeitar opcionais" keeps performance off, stores the choice and hides the notice', async () => {
    // Arrange
    renderWithPlugins(CookieBanner)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Rejeitar opcionais' }))

    // Assert
    expect(stored()).toMatchObject({ version: CONSENT_VERSION, performance: false })
    expect(useConsentStore().allowsPerformance).toBe(false)
    expect(screen.queryByRole('region', { name: 'Aviso de privacidade' })).toBeNull()
  })

  it('"Aceitar tudo" turns performance on', async () => {
    // Arrange
    renderWithPlugins(CookieBanner)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Aceitar tudo' }))

    // Assert
    expect(stored()).toMatchObject({ performance: true })
    expect(useConsentStore().allowsPerformance).toBe(true)
  })

  it('"Personalizar" shows the categories and saves the switch', async () => {
    // Arrange
    renderWithPlugins(CookieBanner)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Personalizar' }))
    expect(screen.getByText('Sempre ativos')).toBeTruthy()
    await fireEvent.click(screen.getByRole('switch', { name: 'Desempenho' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar escolhas' }))

    // Assert
    expect(stored()).toMatchObject({ performance: true })
  })

  it('stays hidden after a choice, and "Preferências de cookies" opens it again', async () => {
    // Arrange
    localStorage.setItem(CONSENT_KEY, JSON.stringify({ version: CONSENT_VERSION, performance: false, decidedAt: '2026-10-23T10:00:00Z' }))
    renderWithPlugins({ components: { CookieBanner, PrivacyLinks }, template: '<PrivacyLinks /><CookieBanner />' })
    expect(screen.queryByRole('region', { name: 'Aviso de privacidade' })).toBeNull()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Preferências de cookies' }))

    // Assert
    expect(screen.getByRole('switch', { name: 'Desempenho' }).getAttribute('aria-checked')).toBe('false')
    expect(useConsentStore().isPanelOpen).toBe(true)
  })

  it('asks again when the notice changes version', () => {
    // Arrange
    localStorage.setItem(CONSENT_KEY, JSON.stringify({ version: CONSENT_VERSION - 1, performance: true, decidedAt: '2025-01-01T00:00:00Z' }))

    // Act & Assert
    expect(readConsent()).toBeNull()
  })
})
