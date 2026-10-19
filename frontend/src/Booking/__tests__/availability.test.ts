import { describe, expect, it } from 'vitest'
import { computeAvailableTimes, isScheduleLocked } from '../availability'
import { createTestSettings } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import type { ScheduleSettings } from '@/Domain/Settings/settings.model'
import { nextDays } from '@/utils/date'

const MONDAY = new Date(2026, 9, 19)
const SUNDAY = new Date(2026, 9, 18)
const SOME_EARLIER_DAY = new Date(2026, 9, 1, 8, 0)

function buildSchedule(overrides: Partial<ScheduleSettings> = {}): ScheduleSettings {
  const { schedule } = createTestSettings()
  schedule.weeklyHours[1] = { weekday: 1, isOpen: true, opensAt: '09:00', closesAt: '11:00' }
  return { ...schedule, slotIntervalMinutes: 30, ...overrides }
}

describe('computeAvailableTimes', () => {
  it('lists every start that fits the service before closing time', () => {
    // Arrange
    const schedule = buildSchedule()

    // Act
    const times = computeAvailableTimes({ date: MONDAY, schedule, durationMinutes: 45, booked: [], now: SOME_EARLIER_DAY })

    // Assert
    expect(times).toEqual(['09:00', '09:30', '10:00'])
  })

  it('returns nothing on a closed day', () => {
    // Arrange
    const schedule = buildSchedule()

    // Act
    const times = computeAvailableTimes({ date: SUNDAY, schedule, durationMinutes: 30, booked: [], now: SOME_EARLIER_DAY })

    // Assert
    expect(times).toEqual([])
  })

  it('skips starts that overlap an existing request', () => {
    // Arrange
    const schedule = buildSchedule()
    const booked = [{ date: '2026-10-19', time: '09:30', durationMinutes: 30 }]

    // Act
    const times = computeAvailableTimes({ date: MONDAY, schedule, durationMinutes: 30, booked, now: SOME_EARLIER_DAY })

    // Assert
    expect(times).toEqual(['09:00', '10:00', '10:30'])
  })

  it('hides times that already passed today', () => {
    // Arrange
    const schedule = buildSchedule()
    const now = new Date(2026, 9, 19, 9, 40)

    // Act
    const times = computeAvailableTimes({ date: MONDAY, schedule, durationMinutes: 30, booked: [], now })

    // Assert
    expect(times).toEqual(['10:00', '10:30'])
  })

  it('returns nothing inside the vacation period', () => {
    // Arrange
    const schedule = buildSchedule()
    schedule.vacation = { ...schedule.vacation, isEnabled: true, startsOn: '2026-10-19', endsOn: '2026-10-25' }

    // Act
    const times = computeAvailableTimes({ date: MONDAY, schedule, durationMinutes: 30, booked: [], now: SOME_EARLIER_DAY })

    // Assert
    expect(times).toEqual([])
  })
})

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
