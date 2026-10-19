import { describe, expect, it } from 'vitest'
import { fireEvent, render, screen } from '@testing-library/vue'
import InfoTooltip from './InfoTooltip.vue'

function renderTooltip() {
  render(InfoTooltip, { props: { label: 'Como a margem é calculada' }, slots: { default: 'Lucro ÷ Faturamento × 100' } })
  const button = screen.getByRole('button', { name: 'Como a margem é calculada' })
  const tooltip = screen.getByText('Lucro ÷ Faturamento × 100').closest('[role="tooltip"]') as HTMLElement
  const isShown = () => tooltip.style.display !== 'none'
  return { button, tooltip, isShown }
}

describe('InfoTooltip', () => {
  it('is hidden by default and describes the button', () => {
    // Arrange & Act
    const { button, tooltip, isShown } = renderTooltip()

    // Assert
    expect(isShown()).toBe(false)
    expect(button.getAttribute('aria-describedby')).toBe(tooltip.id)
  })

  it('shows on mouse hover and hides when the mouse leaves', async () => {
    // Arrange
    const { button, isShown } = renderTooltip()

    // Act
    await fireEvent.mouseEnter(button.parentElement!)
    const shownOnHover = isShown()
    await fireEvent.mouseLeave(button.parentElement!)

    // Assert
    expect(shownOnHover).toBe(true)
    expect(isShown()).toBe(false)
  })

  it('shows on keyboard focus and closes with Esc', async () => {
    // Arrange
    const { button, isShown } = renderTooltip()

    // Act
    await fireEvent.focus(button)
    const shownOnFocus = isShown()
    await fireEvent.keyDown(button, { key: 'Escape' })

    // Assert
    expect(shownOnFocus).toBe(true)
    expect(isShown()).toBe(false)
  })

  it('a tap opens it, keeps it open without hover, and a second tap closes it', async () => {
    // Arrange
    const { button, isShown } = renderTooltip()

    // Act
    await fireEvent.click(button)
    await fireEvent.mouseLeave(button.parentElement!)
    const keptOpen = isShown()
    await fireEvent.click(button)

    // Assert
    expect(keptOpen).toBe(true)
    expect(button.getAttribute('aria-expanded')).toBe('false')
    expect(isShown()).toBe(false)
  })

  it('closes on a tap outside', async () => {
    // Arrange
    const { button, isShown } = renderTooltip()
    await fireEvent.click(button)

    // Act
    await fireEvent.pointerDown(document.body)

    // Assert
    expect(isShown()).toBe(false)
  })
})
