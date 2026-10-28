import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/vue'
import { defineComponent, h, nextTick, ref } from 'vue'
import BaseTabs from './BaseTabs.vue'

const dirty = ref(false)

const Host = defineComponent({
  setup() {
    const tab = ref('link')
    return () =>
      h(BaseTabs, {
        modelValue: tab.value,
        'onUpdate:modelValue': (value: string) => (tab.value = value),
        idPrefix: 'settings',
        label: 'Abas',
        tabs: [
          { id: 'link', label: 'Link' },
          { id: 'portfolio', label: 'Portfólio', hasBadge: dirty.value },
        ],
      })
  },
})

function dot(name: string): HTMLElement {
  const span = screen.getByRole('tab', { name }).querySelector('span')
  if (!span) throw new Error(`missing dot in ${name}`)
  return span
}

describe('BaseTabs', () => {
  it('keeps the unsaved dot in the tab so the menu does not grow', async () => {
    // Arrange
    dirty.value = false
    render(Host)

    // Assert
    expect(dot('Link').className).toContain('invisible')
    expect(dot('Portfólio').className).toContain('invisible')

    // Act
    dirty.value = true
    await nextTick()

    // Assert
    expect(dot('Link').className).toContain('invisible')
    expect(dot('Portfólio').className).not.toContain('invisible')
    expect(dot('Portfólio').className).toContain('bg-accent')
  })
})
