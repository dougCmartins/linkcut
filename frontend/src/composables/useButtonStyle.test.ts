import { beforeEach, describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import { computed, defineComponent, h } from 'vue'
import { provideButtonStyle, useButtonStyle } from './useButtonStyle'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { ButtonStyle } from '@/types/theme'

const Reader = defineComponent({
  setup() {
    const style = useButtonStyle()
    return () => h('span', { 'data-testid': 'style' }, style.value)
  },
})

describe('useButtonStyle', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('uses the style the barber saved', () => {
    // Arrange
    const store = useSettingsStore()
    store.saved.theme.buttonStyle = ButtonStyle.Outline

    // Act
    render(Reader)

    // Assert
    expect(screen.getByTestId('style').textContent).toBe('outline')
  })

  it('uses the provided style when there is one', () => {
    // Arrange
    const Provider = defineComponent({
      setup() {
        provideButtonStyle(computed(() => ButtonStyle.HardShadow))
        return () => h(Reader)
      },
    })

    // Act
    render(Provider)

    // Assert
    expect(screen.getByTestId('style').textContent).toBe('hardShadow')
  })
})
