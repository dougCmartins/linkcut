import { describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
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

  it('the arrows slide one card at a time, smoothly, and switch off at the ends', async () => {
    // Arrange
    renderWithPlugins(ReviewCarousel, { props: { reviews } })
    const list = overflowingList()
    const scrollBy = vi.fn()
    list.scrollBy = scrollBy as unknown as typeof list.scrollBy
    await fireEvent.scroll(list)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Próxima avaliação' }))

    // Assert
    expect(scrollBy).toHaveBeenCalledWith(expect.objectContaining({ behavior: 'smooth' }))
    expect(scrollBy.mock.calls[0]![0].left).toBeGreaterThanOrEqual(0)
    expect(screen.getByRole('button', { name: 'Avaliação anterior' }).hasAttribute('disabled')).toBe(true)

    // Act: at the last card
    list.scrollLeft = 500
    await fireEvent.scroll(list)

    // Assert
    expect(screen.getByRole('button', { name: 'Próxima avaliação' }).hasAttribute('disabled')).toBe(true)
    expect(screen.getByRole('button', { name: 'Avaliação anterior' }).hasAttribute('disabled')).toBe(false)
  })
})
