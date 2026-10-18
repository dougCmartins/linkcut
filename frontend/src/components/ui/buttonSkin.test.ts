import { describe, expect, it } from 'vitest'
import { buttonSkinClasses } from './buttonSkin'
import { BUTTON_STYLES, ButtonStyle } from '@/types/theme'

describe('buttonSkinClasses', () => {
  it('gives every style its own look', () => {
    // Arrange
    const styles = BUTTON_STYLES

    // Act
    const primaryLooks = new Set(styles.map((style) => buttonSkinClasses(style, 'primary')))

    // Assert
    expect(primaryLooks.size).toBe(styles.length)
  })

  it('uses the proportional border for outline and hard shadow', () => {
    // Arrange
    const bordered = [ButtonStyle.Outline, ButtonStyle.HardShadow]

    // Act
    const classes = bordered.map((style) => buttonSkinClasses(style, 'secondary'))

    // Assert
    classes.forEach((value) => expect(value).toContain('button-border'))
    expect(buttonSkinClasses(ButtonStyle.HardShadow, 'primary')).toContain('button-hard-shadow')
  })

  it('has no border on the soft shadow style', () => {
    // Arrange
    const style = ButtonStyle.SoftShadow

    // Act
    const classes = buttonSkinClasses(style, 'primary')

    // Assert
    expect(classes).toContain('button-soft-shadow')
    expect(classes).not.toContain('button-border')
  })

  it('keeps the original 3D edge for the elevated style', () => {
    // Arrange
    const style = ButtonStyle.Elevated

    // Act
    const classes = buttonSkinClasses(style, 'primary')

    // Assert
    expect(classes).toContain('bg-accent')
    expect(classes).toContain('shadow-[0_6px_0_0_var(--edge),var(--shadow-soft)]')
  })
})
