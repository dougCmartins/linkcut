import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import SettingsView from '../SettingsView.vue'
import { useSettingsStore } from '../settings.store'
import { renderWithPlugins } from '@/utils/testRender'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { USER, identityRoutes } from '@/Domain/Identity/__tests__/fakeIdentityApi'
import { TwoFactorChannel } from '@/types/twoFactorChannel'
import { envelope, installFakeApi } from '@/utils/testFetch'
import { installFakeSettingsApi } from './fakeSettingsApi'

function signedInSession(availableChannels: TwoFactorChannel[] = [TwoFactorChannel.Email, TwoFactorChannel.Sms]) {
  const now = Date.now()
  return {
    accessToken: 'access-1',
    refreshToken: 'refresh-1',
    accessExpiresAt: now + 60_000,
    refreshExpiresAt: now + 120_000,
    user: { id: 7, tenantId: 3, name: 'Rafael', email: 'rafael@linkcut.app', phoneMasked: '+55 •• •••••-7777', role: 'owner', twoFactorChannel: null, availableChannels },
  }
}

async function renderSettings(path = '/painel/configuracoes') {
  const rendered = renderWithPlugins(SettingsView)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('SettingsView', () => {
  let api: ReturnType<typeof installFakeSettingsApi>

  beforeEach(() => {
    sessionStorage.clear()
    api = installFakeSettingsApi(undefined, identityRoutes())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('opens on the Link tab and moves between tabs with the arrow keys', async () => {
    // Arrange
    await renderSettings()
    const linkTab = await screen.findByRole('tab', { name: 'Link', selected: true })

    // Act
    await fireEvent.keyDown(linkTab, { key: 'ArrowRight' })

    // Assert
    expect(await screen.findByRole('tab', { name: 'Portfólio', selected: true })).toBeTruthy()
    expect(await screen.findByLabelText('Sua história')).toBeTruthy()
  })

  it('opens the tab named in the URL', async () => {
    // Arrange & Act
    await renderSettings('/painel/configuracoes?aba=agenda')

    // Assert
    expect(await screen.findByRole('tab', { name: 'Agenda', selected: true })).toBeTruthy()
    expect(screen.getByRole('switch', { name: 'Travar agenda' })).toBeTruthy()
  })

  it('blocks saving while a field has an error', async () => {
    // Arrange
    await renderSettings()
    const nameInput = await screen.findByLabelText('Nome')

    // Act
    await fireEvent.update(nameInput, '')

    // Assert
    expect(screen.getByText('Campo obrigatório.')).toBeTruthy()
    expect(screen.getByText('Corrija os campos destacados')).toBeTruthy()
    expect((screen.getByRole('button', { name: 'Salvar' }) as HTMLButtonElement).disabled).toBe(true)
  })

  it('publishes the changes when the barber saves', async () => {
    // Arrange
    await renderSettings()
    const store = useSettingsStore()
    await fireEvent.update(await screen.findByLabelText('Nome'), 'Diego Souza')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    expect(await screen.findByText('Alterações salvas!')).toBeTruthy()
    expect(store.profile.name).toBe('Diego Souza')
    expect(api.current.profile.name).toBe('Diego Souza')
    expect(screen.queryByRole('button', { name: 'Salvar' })).toBeNull()
  })

  it('puts back the saved values when the barber discards', async () => {
    // Arrange
    await renderSettings()
    const nameInput = (await screen.findByLabelText('Nome')) as HTMLInputElement
    await fireEvent.update(nameInput, 'Outro nome')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Descartar' }))

    // Assert
    expect(nameInput.value).toBe('Rafael Costa')
  })

  it('previews the chosen palette before saving', async () => {
    // Arrange
    await renderSettings('/painel/configuracoes?aba=aparencia')
    const store = useSettingsStore()

    // Act
    await fireEvent.click(await screen.findByRole('radio', { name: /Oceano/ }))
    await fireEvent.click(screen.getByRole('radio', { name: 'Claro' }))

    // Assert
    const preview = screen.getByTestId('phone-preview')
    expect(preview.dataset.theme).toBe('ocean')
    expect(preview.dataset.mode).toBe('light')
    expect(store.theme.palette).toBe('default')
  })

  it('previews and saves the button shape', async () => {
    // Arrange
    await renderSettings('/painel/configuracoes?aba=aparencia')
    const store = useSettingsStore()

    // Act
    await fireEvent.click(await screen.findByRole('radio', { name: 'Quadrado' }))
    const previewShape = screen.getByTestId('phone-preview').dataset.buttonShape
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))
    await screen.findByText('Alterações salvas!')

    // Assert
    expect(previewShape).toBe('square')
    expect(store.theme.buttonShape).toBe('square')
  })

  it('saves the default 2FA channel on the server right away (Segurança tab)', async () => {
    // Arrange
    const { calls } = api
    await renderSettings('/painel/configuracoes?aba=seguranca')
    const auth = useAuthStore()
    auth.session = signedInSession()
    expect(await screen.findByRole('tab', { name: 'Segurança', selected: true })).toBeTruthy()
    expect(screen.getByRole('radio', { name: /Perguntar sempre/ }).getAttribute('aria-checked')).toBe('true')

    // Act
    await fireEvent.click(screen.getByRole('radio', { name: /SMS/ }))

    // Assert
    expect(await screen.findByText('Alterações salvas!')).toBeTruthy()
    expect(calls.find((call) => call.method === 'PATCH')?.body).toEqual({ channel: 'sms' })
    expect(auth.user?.twoFactorChannel).toBe('sms')
    expect(screen.getByRole('radio', { name: /SMS/ }).getAttribute('aria-checked')).toBe('true')
    expect(screen.queryByRole('button', { name: 'Salvar' })).toBeNull()
  })

  it('hides SMS while the API has it off (code only by e-mail)', async () => {
    // Arrange
    api = installFakeSettingsApi(undefined, {
      ...identityRoutes(),
      'GET /me': () => ({ status: 200, body: { ...USER, available_channels: ['email'] } }),
    })

    // Act
    await renderSettings('/painel/configuracoes?aba=seguranca')
    useAuthStore().session = signedInSession([TwoFactorChannel.Email])

    // Assert
    expect(await screen.findByRole('radio', { name: /E-mail/ })).toBeTruthy()
    expect(screen.queryByRole('radio', { name: /SMS/ })).toBeNull()
  })

  it('previews and saves the button style', async () => {
    // Arrange
    await renderSettings('/painel/configuracoes?aba=aparencia')
    const store = useSettingsStore()

    // Act
    await fireEvent.click(await screen.findByRole('radio', { name: 'Contorno' }))
    const previewButtons = [...screen.getByTestId('preview-buttons').children]
    const savedBefore = store.theme.buttonStyle
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))
    await screen.findByText('Alterações salvas!')

    // Assert
    expect(previewButtons.every((button) => button.classList.contains('button-border'))).toBe(true)
    expect(savedBefore).toBe('fill')
    expect(store.theme.buttonStyle).toBe('outline')
  })

  it('shows the API error and keeps the changes when saving fails', async () => {
    // Arrange
    await renderSettings()
    await fireEvent.update(await screen.findByLabelText('Nome'), 'Diego Souza')
    installFakeApi({ 'PUT /settings/profile': () => envelope('IMAGE_NOT_FOUND', 422) })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    expect(await screen.findByText('Uma das imagens não está mais disponível. Envie de novo.')).toBeTruthy()
    expect((screen.getByLabelText('Nome') as HTMLInputElement).value).toBe('Diego Souza')
    expect(screen.getByRole('button', { name: 'Salvar' })).toBeTruthy()
  })

  it('offers a retry when the settings cannot be loaded', async () => {
    // Arrange
    installFakeApi({ 'GET /settings': () => envelope('SERVER_ERROR', 500) })
    await renderSettings()
    expect(await screen.findByText('Não foi possível carregar suas configurações.')).toBeTruthy()
    api = installFakeSettingsApi()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Tentar de novo' }))

    // Assert
    expect(await screen.findByRole('tab', { name: 'Link', selected: true })).toBeTruthy()
  })
})
