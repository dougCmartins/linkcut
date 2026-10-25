import { describe, expect, it } from 'vitest'
import { fromMinutes, toMinutes, formatDuration } from './time'

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

describe('formatDuration', () => {
  it('shows minutes, whole hours and hours with minutes', () => {
    // Arrange
    const lengths = [45, 60, 75, 125]

    // Act
    const labels = lengths.map(formatDuration)

    // Assert
    expect(labels).toEqual(['45 min', '1h', '1h15', '2h05'])
  })
})
