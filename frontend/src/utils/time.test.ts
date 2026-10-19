import { describe, expect, it } from 'vitest'
import { fromMinutes, toMinutes } from './time'

describe('toMinutes / fromMinutes', () => {
  it('converts a clock time to minutes since midnight', () => {
    // Arrange
    const time = '13:30'

    // Act
    const minutes = toMinutes(time)

    // Assert
    expect(minutes).toBe(810)
  })

  it('formats minutes back with leading zeros', () => {
    // Arrange
    const minutes = 545

    // Act
    const time = fromMinutes(minutes)

    // Assert
    expect(time).toBe('09:05')
  })
})
