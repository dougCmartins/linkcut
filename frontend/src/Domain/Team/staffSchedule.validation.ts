import type { StaffDayHours } from './staffSchedule.model'

/** i18n key (under team.agenda.errors) of what is wrong in each day, by "weeklyHours.<index>". */
export function validateStaffHours(days: StaffDayHours[]): Record<string, string> {
  const errors: Record<string, string> = {}
  days.forEach((day, index) => {
    if (!day.isOpen) return
    if (!day.opensAt || !day.closesAt || day.closesAt <= day.opensAt) {
      errors[`weeklyHours.${index}`] = 'hoursOrder'
      return
    }
    const { breakStartsAt: start, breakEndsAt: end } = day
    if (start === null && end === null) return
    if (!start || !end || end <= start || start <= day.opensAt || end >= day.closesAt) {
      errors[`weeklyHours.${index}`] = 'breakOutside'
    }
  })
  return errors
}
