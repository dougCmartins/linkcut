import { describe, expect, it } from 'vitest'
import { fireEvent, render, screen } from '@testing-library/vue'
import { defineComponent, h, ref } from 'vue'
import OtpInput from './OtpInput.vue'

function renderOtp() {
  const code = ref('')
  const completed: string[] = []
  const Host = defineComponent({
    setup: () => () =>
      h(OtpInput, {
        label: 'Código',
        modelValue: code.value,
        'onUpdate:modelValue': (value: string) => (code.value = value),
        onComplete: (value: string) => completed.push(value),
      }),
  })
  render(Host)
  return { code, completed, boxes: screen.getAllByRole('textbox') as HTMLInputElement[] }
}

describe('OtpInput', () => {
  it('renders one box per digit', () => {
    // Arrange & Act
    const { boxes } = renderOtp()

    // Assert
    expect(boxes).toHaveLength(6)
    expect(boxes[0]?.getAttribute('autocomplete')).toBe('one-time-code')
  })

  it('fills every box from a pasted code and reports completion', async () => {
    // Arrange
    const { boxes, code, completed } = renderOtp()

    // Act
    await fireEvent.paste(boxes[0]!, { clipboardData: { getData: () => '48 29-13' } })

    // Assert
    expect(code.value).toBe('482913')
    expect(completed).toEqual(['482913'])
  })

  it('ignores letters while typing', async () => {
    // Arrange
    const { boxes, code } = renderOtp()

    // Act
    await fireEvent.update(boxes[0]!, 'a')
    await fireEvent.update(boxes[0]!, '7')

    // Assert
    expect(code.value).toBe('7')
  })

  it('clears the previous box on backspace from an empty one', async () => {
    // Arrange
    const { boxes, code } = renderOtp()
    await fireEvent.update(boxes[0]!, '1')
    await fireEvent.update(boxes[1]!, '2')

    // Act
    await fireEvent.keyDown(boxes[2]!, { key: 'Backspace' })

    // Assert
    expect(code.value).toBe('1')
  })
})
