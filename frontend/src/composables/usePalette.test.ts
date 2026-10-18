import { beforeEach, describe, expect, it } from 'vitest'
import { usePalette } from './usePalette'
import { Palette } from '@/types/palette'

describe('usePalette', () => {
  beforeEach(() => {
    usePalette().applyPalette(Palette.Dark)
  })

  it('switches from dark to light and updates the html attribute', () => {
    // Arrange
    const { palette, togglePalette } = usePalette()

    // Act
    togglePalette()

    // Assert
    expect(palette.value).toBe(Palette.Light)
    expect(document.documentElement.dataset.palette).toBe(Palette.Light)
  })

  it('remembers the chosen palette', () => {
    // Arrange
    const { applyPalette } = usePalette()

    // Act
    applyPalette(Palette.Light)

    // Assert
    expect(localStorage.getItem('linkcut.palette')).toBe(Palette.Light)
  })
})
