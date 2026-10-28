import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import ReviewCarousel from '../ReviewCarousel.vue'
import { renderWithPlugins } from '@/utils/testRender'

const reviews = [
  { firstName: 'Ana', rating: 5, comment: 'Corte preciso e sem pressa.' },
  { firstName: 'Bruno', rating: 4, comment: 'Ambiente calmo e a barba ficou alinhada.' },
]

function overflowingList(): HTMLElement {
  const list = screen.getByRole('list')
  Object.defineProperty(list, 'clientWidth', { configurable: true, value: 300 })
  Object.defineProperty(list, 'scrollWidth', { configurable: true, value: 800 })
  return list
}

describe('ReviewCarousel', () => {
  it('hides the bar and lets the wheel slide the cards', () => {
    // Arrange
    renderWithPlugins(ReviewCarousel, { props: { reviews } })
    const list = overflowingList()

    // Act
    const wheel = new WheelEvent('wheel', { deltaY: 120, cancelable: true })
    list.dispatchEvent(wheel)

    // Assert
    expect(list.className).toContain('scrollbar-none')
    expect(list.scrollLeft).toBe(120)
    expect(wheel.defaultPrevented).toBe(true)
  })

  it('lets the page scroll when the row is already at the end', () => {
    // Arrange
    renderWithPlugins(ReviewCarousel, { props: { reviews } })
    const list = overflowingList()
    list.scrollLeft = 500

    // Act
    const wheel = new WheelEvent('wheel', { deltaY: 120, cancelable: true })
    list.dispatchEvent(wheel)

    // Assert
    expect(list.scrollLeft).toBe(500)
    expect(wheel.defaultPrevented).toBe(false)
  })
})
