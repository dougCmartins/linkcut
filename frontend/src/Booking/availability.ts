import type { ScheduleSettings, VacationLock } from '@/Settings/settings.model'
import { toIsoDate } from '@/utils/date'
import { fromMinutes, toMinutes } from '@/utils/time'

/** A time already taken by a pending or confirmed request. */
export interface BookedSlot {
  date: string
  time: string
  durationMinutes: number
}

export interface AvailabilityQuery {
  date: Date
  schedule: ScheduleSettings
  durationMinutes: number
  booked: BookedSlot[]
  now: Date
}

export function isOnVacation(date: Date, vacation: VacationLock): boolean {
  if (!vacation.isEnabled) return false
  const isoDate = toIsoDate(date)
  if (vacation.startsOn && isoDate < vacation.startsOn) return false
  if (vacation.endsOn && isoDate > vacation.endsOn) return false
  return true
}

/** True when every day in the booking window is inside the vacation lock. */
export function isScheduleLocked(days: Date[], vacation: VacationLock): boolean {
  return days.length > 0 && days.every((day) => isOnVacation(day, vacation))
}

/** Start times a client can request on `date`, as "HH:mm". */
export function computeAvailableTimes({ date, schedule, durationMinutes, booked, now }: AvailabilityQuery): string[] {
  if (isOnVacation(date, schedule.vacation)) return []
  const day = schedule.weeklyHours.find((hours) => hours.weekday === date.getDay())
  if (!day?.isOpen) return []

  const isoDate = toIsoDate(date)
  const isToday = isoDate === toIsoDate(now)
  const nowMinutes = now.getHours() * 60 + now.getMinutes()
  const bookedRanges = booked
    .filter((slot) => slot.date === isoDate)
    .map((slot) => ({ start: toMinutes(slot.time), end: toMinutes(slot.time) + slot.durationMinutes }))

  const closesAt = toMinutes(day.closesAt)
  const times: string[] = []
  for (let start = toMinutes(day.opensAt); start + durationMinutes <= closesAt; start += schedule.slotIntervalMinutes) {
    const end = start + durationMinutes
    const overlapsBooking = bookedRanges.some((range) => start < range.end && range.start < end)
    const hasPassed = isToday && start <= nowMinutes
    if (!overlapsBooking && !hasPassed) times.push(fromMinutes(start))
  }
  return times
}
