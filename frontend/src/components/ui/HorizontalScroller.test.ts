import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, render, screen } from '@testing-library/vue'
import { defineComponent, h, nextTick, ref } from 'vue'
import HorizontalScroller from './HorizontalScroller.vue'

/** Small host with three radio items, the middle or last one selectable. */
const Host = defineComponent({
  props: { initial: { type: String, default: 'a' } },
  setup(props) {
    const selected = ref(props.initial)
    const onSelect = vi.fn()
    return () =>
      h('div', [
        h('button', { type: 'button', 'data-testid': 'pick-c', onClick: () => (selected.value = 'c') }),
        h(
          HorizontalScroller,
          { label: 'Dias', selectedKey: selected.value },
          () =>
            ['a', 'b', 'c'].map((key) =>
              h('button', { type: 'button', role: 'radio', 'aria-checked': key === selected.value, onClick: onSelect }, key),
            ),
        ),
      ])
  },
})

describe('HorizontalScroller', () => {
  let scrollTo: ReturnType<typeof vi.fn>

  beforeEach(() => {
    scrollTo = vi.fn()
    HTMLElement.prototype.scrollTo = scrollTo as unknown as typeof HTMLElement.prototype.scrollTo
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('renders the items inside a labelled radiogroup', () => {
    // Arrange & Act
    render(Host)

    // Assert
    const group = screen.getByRole('radiogroup', { name: 'Dias' })
    expect(group.querySelectorAll('[role=radio]')).toHaveLength(3)
  })

  it('does not fade the edges when nothing overflows', () => {
    // Arrange & Act
    render(Host)

    // Assert
    const group = screen.getByRole('radiogroup', { name: 'Dias' })
    expect(group.style.maskImage).toBe('')
  })

  it('scrolls to the newly selected item', async () => {
    // Arrange
    render(Host)
    scrollTo.mockClear()

    // Act
    await fireEvent.click(screen.getByTestId('pick-c'))
    await nextTick()

    // Assert
    expect(scrollTo).toHaveBeenCalledWith(expect.objectContaining({ behavior: 'smooth' }))
  })

  it('lets a plain click select an item', async () => {
    // Arrange
    render(Host)
    const item = screen.getByRole('radio', { name: 'b' })
    const clicked = vi.fn()
    item.addEventListener('click', clicked)

    // Act
    await fireEvent.click(item)

    // Assert
    expect(clicked).toHaveBeenCalledTimes(1)
  })
})
