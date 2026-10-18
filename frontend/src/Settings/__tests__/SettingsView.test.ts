import { beforeEach, describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import SettingsView from '../SettingsView.vue'
import { useSettingsStore } from '../settings.store'
import { renderWithPlugins } from '@/utils/testRender'

async function renderSettings(path = '/painel/configuracoes') {
  const rendered = renderWithPlugins(SettingsView)
  await rendered.router.push(path)
  await rendered.router.isReady()
  return rendered
}

describe('SettingsView', () => {
  beforeEach(() => {
    localStorage.clear()
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
    expect(store.profile.name).toBe('Diego Souza')
    expect(screen.getByText('Alterações salvas!')).toBeTruthy()
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

    // Assert
    expect(previewShape).toBe('square')
    expect(store.theme.buttonShape).toBe('square')
  })

  it('saves the default 2FA channel in the Segurança tab', async () => {
    // Arrange
    await renderSettings('/painel/configuracoes?aba=seguranca')
    const store = useSettingsStore()
    expect(await screen.findByRole('tab', { name: 'Segurança', selected: true })).toBeTruthy()
    expect(screen.getByRole('radio', { name: /Perguntar sempre/ }).getAttribute('aria-checked')).toBe('true')

    // Act
    await fireEvent.click(screen.getByRole('radio', { name: /WhatsApp/ }))
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    expect(store.security.defaultTwoFactorChannel).toBe('whatsapp')
    expect(await screen.findByText('(11) •••••-8888')).toBeTruthy()
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

    // Assert
    expect(previewButtons.every((button) => button.classList.contains('button-border'))).toBe(true)
    expect(savedBefore).toBe('elevated')
    expect(store.theme.buttonStyle).toBe('outline')
  })
})
