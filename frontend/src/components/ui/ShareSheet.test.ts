import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import ShareSheet from './ShareSheet.vue'
import { renderWithPlugins } from '@/utils/testRender'

const props = { url: 'https://linkcut.app/rafael', name: 'Rafael Costa', imageUrl: null }

describe('ShareSheet', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.useRealTimers()
  })

  it('shows the card of the page and a ready message for each network', () => {
    // Arrange & Act
    renderWithPlugins(ShareSheet, { props })

    // Assert
    expect(screen.getByRole('dialog', { name: 'Compartilhar' })).toBeTruthy()
    expect(screen.getByText('linkcut.app/rafael')).toBeTruthy()
    const whatsapp = new URL(screen.getByRole('link', { name: 'Compartilhar no WhatsApp' }).getAttribute('href')!)
    expect(whatsapp.searchParams.get('text')).toBe('Agende seu horário na Rafael Costa: https://linkcut.app/rafael')
    expect(screen.getByRole('link', { name: 'Compartilhar no Facebook' }).getAttribute('href')).toContain('facebook.com/sharer')
  })

  it('copies the link and says so for a moment', async () => {
    // Arrange
    vi.useFakeTimers()
    const writeText = vi.fn().mockResolvedValue(undefined)
    vi.stubGlobal('navigator', { ...navigator, clipboard: { writeText } })
    renderWithPlugins(ShareSheet, { props })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Copiar link' }))
    await vi.waitFor(() => expect(screen.getByRole('button', { name: 'Link copiado!' })).toBeTruthy())
    vi.advanceTimersByTime(2000)

    // Assert
    expect(writeText).toHaveBeenCalledWith('https://linkcut.app/rafael')
    await vi.waitFor(() => expect(screen.getByRole('button', { name: 'Copiar link' })).toBeTruthy())
  })

  it('offers "Mais opções" only where the phone has a share sheet', async () => {
    // Arrange
    const share = vi.fn().mockResolvedValue(undefined)
    vi.stubGlobal('navigator', { ...navigator, share })

    // Act
    renderWithPlugins(ShareSheet, { props })
    await fireEvent.click(screen.getByRole('button', { name: 'Mais opções' }))

    // Assert
    expect(share).toHaveBeenCalledWith({ title: 'Rafael Costa', text: 'Agende seu horário na Rafael Costa:', url: 'https://linkcut.app/rafael' })
  })

  it('has no "Mais opções" without a share sheet and closes with Escape', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(ShareSheet, { props })

    // Act
    await fireEvent.keyDown(window, { key: 'Escape' })

    // Assert
    expect(screen.queryByRole('button', { name: 'Mais opções' })).toBeNull()
    expect(emitted('close')).toHaveLength(1)
  })
})
