/**
 * "Adicionar à agenda": Google Calendar link and an .ics file (Apple, Outlook, Android). Times are "floating"
 * (no timezone): the event lands at the same clock time on the phone, which is where the barbershop is.
 */
export interface CalendarEvent {
  title: string
  /** "YYYY-MM-DD" and "HH:mm". */
  date: string
  startTime: string
  endTime: string
  location: string
  details: string
}

function stamp(date: string, time: string): string {
  return `${date.replaceAll('-', '')}T${time.replace(':', '')}00`
}

export function googleCalendarUrl(event: CalendarEvent): string {
  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: event.title,
    dates: `${stamp(event.date, event.startTime)}/${stamp(event.date, event.endTime)}`,
    location: event.location,
    details: event.details,
  })
  return `https://calendar.google.com/calendar/render?${params.toString()}`
}

/** Text values of an .ics line: backslash, comma, semicolon and line breaks escaped. */
function escapeIcs(value: string): string {
  return value.replace(/\\/g, '\\\\').replace(/([,;])/g, '\\$1').replace(/\r?\n/g, '\\n')
}

export function icsContent(event: CalendarEvent, uid: string, now: Date = new Date()): string {
  const created = now.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '')
  return [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//linkcut//agenda//PT',
    'BEGIN:VEVENT',
    `UID:${uid}`,
    `DTSTAMP:${created}`,
    `DTSTART:${stamp(event.date, event.startTime)}`,
    `DTEND:${stamp(event.date, event.endTime)}`,
    `SUMMARY:${escapeIcs(event.title)}`,
    `LOCATION:${escapeIcs(event.location)}`,
    `DESCRIPTION:${escapeIcs(event.details)}`,
    'BEGIN:VALARM',
    'TRIGGER:-PT1H',
    'ACTION:DISPLAY',
    `DESCRIPTION:${escapeIcs(event.title)}`,
    'END:VALARM',
    'END:VEVENT',
    'END:VCALENDAR',
  ].join('\r\n')
}

/** data: URL for <a download>: no blob to revoke, works offline. */
export function icsDataUrl(event: CalendarEvent, uid: string): string {
  return `data:text/calendar;charset=utf-8,${encodeURIComponent(icsContent(event, uid))}`
}
