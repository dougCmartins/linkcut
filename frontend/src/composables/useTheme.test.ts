import { describe, expect, it } from 'vitest'
import { applyThemeToDocument, themeAttributes, useVisitorMode } from './useTheme'
import { ThemeMode, ThemePalette } from '@/types/theme'

const oceanDark = { palette: ThemePalette.Ocean, mode: ThemeMode.Dark, customAccent: null }

describe('applyThemeToDocument', () => {
  it('sets the palette and mode on <html>', () => {
    // Arrange
    const theme = oceanDark

    // Act
    applyThemeToDocument(theme)

    // Assert
    expect(document.documentElement.dataset.theme).toBe('ocean')
    expect(document.documentElement.dataset.mode).toBe('dark')
  })

  it('applies a custom accent with a readable text color, and removes it when turned off', () => {
    // Arrange
    const withAccent = { ...oceanDark, customAccent: '#f5d90a' }

    // Act
    applyThemeToDocument(withAccent)
    const accent = document.documentElement.style.getPropertyValue('--accent')
    const onAccent = document.documentElement.style.getPropertyValue('--on-accent')
    applyThemeToDocument(oceanDark)

    // Assert
    expect(accent).toBe('#f5d90a')
    expect(onAccent).toBe('#000000')
    expect(document.documentElement.style.getPropertyValue('--accent')).toBe('')
  })

  it('lets a mode override win over the barber default', () => {
    // Arrange
    const override = ThemeMode.Light

    // Act
    const attributes = themeAttributes(oceanDark, override)

    // Assert
    expect(attributes['data-mode']).toBe('light')
  })
})

describe('useVisitorMode', () => {
  it('flips the current mode and remembers the choice', () => {
    // Arrange
    const { visitorMode, toggleVisitorMode } = useVisitorMode()

    // Act
    toggleVisitorMode(ThemeMode.Dark)

    // Assert
    expect(visitorMode.value).toBe(ThemeMode.Light)
    expect(localStorage.getItem('linkcut.visitorMode')).toBe('light')
  })
})
