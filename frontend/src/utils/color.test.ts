import { describe, expect, it } from 'vitest'
import { contrastRatio, cssColorToHex, darken, readableTextOn } from './color'

describe('contrastRatio', () => {
  it('returns 21 for black on white', () => {
    // Arrange
    const black = '#000000'
    const white = '#ffffff'

    // Act
    const ratio = contrastRatio(black, white)

    // Assert
    expect(ratio).toBeCloseTo(21, 1)
  })

  it('is symmetric', () => {
    // Arrange
    const gold = '#d6a740'
    const page = '#110c1a'

    // Act
    const forward = contrastRatio(gold, page)
    const backward = contrastRatio(page, gold)

    // Assert
    expect(forward).toBeCloseTo(backward, 5)
  })
})

describe('readableTextOn', () => {
  it('picks black text on a light accent', () => {
    // Arrange
    const yellow = '#f5d90a'

    // Act
    const text = readableTextOn(yellow)

    // Assert
    expect(text).toBe('#000000')
  })

  it('picks white text on a dark accent', () => {
    // Arrange
    const navy = '#1d2b64'

    // Act
    const text = readableTextOn(navy)

    // Assert
    expect(text).toBe('#ffffff')
  })
})

describe('darken', () => {
  it('mixes the color with black', () => {
    // Arrange
    const color = '#ff8040'

    // Act
    const darker = darken(color, 0.5)

    // Assert
    expect(darker).toBe('#804020')
  })
})

describe('cssColorToHex', () => {
  it('converts a computed rgb() value', () => {
    // Arrange
    const computed = 'rgb(17, 12, 26)'

    // Act
    const hex = cssColorToHex(computed)

    // Assert
    expect(hex).toBe('#110c1a')
  })

  it('returns null for values it cannot read', () => {
    // Arrange
    const computed = 'color-mix(in srgb, red, blue)'

    // Act
    const hex = cssColorToHex(computed)

    // Assert
    expect(hex).toBeNull()
  })
})
