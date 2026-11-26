import type { DayHours } from '@/Domain/Settings/settings.model'

/** A day of a barber's working hours, with the optional break (e.g. lunch). */
export interface StaffDayHours extends DayHours {
  breakStartsAt: string | null
  breakEndsAt: string | null
}

/** A block in the barber's agenda: once (a day or a range) or every week on some weekdays. */
export interface StaffTimeBlock {
  id: number
  /** "YYYY-MM-DD HH:mm" in the unit's timezone. Weekly: the first day and the time of day. */
  startsAt: string
  /** Weekly: same day as startsAt = no end; a later day = the last occurrence. */
  endsAt: string
  recurrence: 'weekly' | null
  /** 0 = Sunday … 6 = Saturday (weekly only). */
  weekdays: number[] | null
  note: string | null
}

export type NewStaffTimeBlock = Omit<StaffTimeBlock, 'id'>
