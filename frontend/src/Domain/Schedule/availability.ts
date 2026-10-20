import type { VacationLock } from '@/Domain/Settings/settings.model'
import { toIsoDate } from '@/utils/date'

/** The free times come from the API; these only explain an empty day or a locked agenda to the client. */

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
