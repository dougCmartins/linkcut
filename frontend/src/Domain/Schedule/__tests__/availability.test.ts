import { describe, expect, it } from 'vitest'
import { isOnVacation, isScheduleLocked } from '../availability'
import { createTestSettings } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import type { ScheduleSettings } from '@/Domain/Settings/settings.model'
import { nextDays } from '@/utils/date'

const MONDAY = new Date(2026, 9, 19)

function buildSchedule(overrides: Partial<ScheduleSettings> = {}): ScheduleSettings {
  const { schedule } = createTestSettings()
  schedule.weeklyHours[1] = { weekday: 1, isOpen: true, opensAt: '09:00', closesAt: '11:00' }
  return { ...schedule, slotIntervalMinutes: 30, ...overrides }
}

describe('isScheduleLocked', () => {
  it('locks the whole window when vacation has no dates', () => {
    // Arrange
    const { vacation } = buildSchedule()
    const lock = { ...vacation, isEnabled: true, startsOn: null, endsOn: null }

    // Act
    const isLocked = isScheduleLocked(nextDays(7, MONDAY), lock)

    // Assert
    expect(isLocked).toBe(true)
  })

  it('stays open when only part of the window is on vacation', () => {
    // Arrange
    const { vacation } = buildSchedule()
    const lock = { ...vacation, isEnabled: true, startsOn: '2026-10-21', endsOn: '2026-10-22' }

    // Act
    const isLocked = isScheduleLocked(nextDays(7, MONDAY), lock)

    // Assert
    expect(isLocked).toBe(false)
  })
})

describe('isOnVacation', () => {
  it('covers the days between the start and the end, both included', () => {
    // Arrange
    const vacation = { isEnabled: true, startsOn: '2026-10-19', endsOn: '2026-10-20', message: '' }

    // Act
    const days = ['2026-10-18', '2026-10-19', '2026-10-20', '2026-10-21'].map((date) => isOnVacation(new Date(`${date}T12:00`), vacation))

    // Assert
    expect(days).toEqual([false, true, true, false])
  })
})
