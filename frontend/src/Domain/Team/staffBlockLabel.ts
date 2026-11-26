import { formatDayMonth, formatWeekdayLong, fromIsoDate } from '@/utils/date'
import type { StaffTimeBlock } from './staffSchedule.model'

type Translate = (key: string, params?: Record<string, string>) => string

/** "YYYY-MM-DD HH:mm" → its date and time parts (no Date parsing: the unit's own clock). */
export function splitDateTime(value: string): { date: string; time: string } {
  const [date = '', time = ''] = value.split(' ')
  return { date, time: time.slice(0, 5) }
}

/** "toda quarta-feira, 13:00–18:00", "26 nov, 13:00–18:00" or "26 nov 13:00 → 28 nov 18:00". */
export function blockLabel(block: StaffTimeBlock, t: Translate): string {
  const start = splitDateTime(block.startsAt)
  const end = splitDateTime(block.endsAt)
  const hours = `${start.time}–${end.time}`
  if (block.recurrence === 'weekly') {
    const days = (block.weekdays ?? []).map((weekday) => formatWeekdayLong(weekday)).join(', ')
    const until = start.date === end.date ? '' : t('team.agenda.blocks.until', { date: formatDayMonth(fromIsoDate(end.date)) })
    return `${t('team.agenda.blocks.every', { days })}, ${hours}${until}`
  }
  if (start.date === end.date) return `${formatDayMonth(fromIsoDate(start.date))}, ${hours}`
  return `${formatDayMonth(fromIsoDate(start.date))} ${start.time} → ${formatDayMonth(fromIsoDate(end.date))} ${end.time}`
}
