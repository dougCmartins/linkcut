import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import OnboardingView from '../OnboardingView.vue'
import { TENANT, createTestSettings, installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

/** A barbershop just created by the sign-up: default settings, no WhatsApp yet. */
function newBarbershop() {
  const settings = createTestSettings()
  settings.profile.whatsappNumber = ''
  return settings
}

async function renderWizard() {
  let onboarded = false
  const fake = installFakeSettingsApi(newBarbershop(), {
    'GET /tenant': () => ({ status: 200, body: { ...TENANT, onboarded } }),
    'POST /tenant/onboarding/complete': () => {
      onboarded = true
      return { status: 201, body: { ...TENANT, onboarded } }
    },
  })
  const rendered = renderWithPlugins(OnboardingView)
  await rendered.router.push('/painel/boas-vindas')
  await rendered.router.isReady()
  return { ...rendered, ...fake, isOnboarded: () => onboarded }
}

describe('OnboardingView', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('asks for the WhatsApp before going on, without errors on the untouched form', async () => {
    // Arrange
    const { calls } = await renderWizard()
    await screen.findByLabelText('WhatsApp da barbearia')
    expect(screen.queryByText(/Informe um telefone com DDD/)).toBeNull()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Continuar' }))

    // Assert
    expect(await screen.findByText('Confira os campos destacados.')).toBeTruthy()
    expect(screen.getByText(/Informe um telefone com DDD/)).toBeTruthy()
    expect(calls.some((call) => call.method === 'PUT')).toBe(false)
  })

  it('saves each step and finishes with the public link', async () => {
    // Arrange
    const { calls, router, isOnboarded } = await renderWizard()
    await fireEvent.update(await screen.findByLabelText('WhatsApp da barbearia'), '(11) 97777-6666')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Continuar' }))
    await screen.findByText('Serviços e horários')
    await fireEvent.click(screen.getByRole('button', { name: 'Continuar' }))
    await screen.findByText('Sua página está no ar!')
    await fireEvent.click(screen.getByRole('button', { name: 'Ir para o painel' }))

    // Assert
    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/painel'))
    expect(calls.find((call) => call.path === '/settings/profile')?.body).toMatchObject({ whatsapp_number: '(11) 97777-6666' })
    expect(calls.some((call) => call.path === '/settings/schedule')).toBe(true)
    expect(isOnboarded()).toBe(true)
  })
})
