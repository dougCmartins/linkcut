import { describe, expect, it } from 'vitest'
import { googleCalendarUrl, icsContent, type CalendarEvent } from './calendar'

const EVENT: CalendarEvent = {
  title: 'Corte · Navalha',
  date: '2026-10-21',
  startTime: '15:00',
  endTime: '15:45',
  location: 'Rua Augusta, 1500 – Consolação, São Paulo/SP',
  details: 'Agendado pelo linkcut',
}

describe('calendar', () => {
  it('builds a Google Calendar link with the local start and end', () => {
    // Arrange / Act
    const url = new URL(googleCalendarUrl(EVENT))

    // Assert
    expect(url.host).toBe('calendar.google.com')
    expect(url.searchParams.get('dates')).toBe('20261021T150000/20261021T154500')
    expect(url.searchParams.get('text')).toBe('Corte · Navalha')
  })

  it('writes an .ics event with escaped text and a reminder one hour before', () => {
    // Arrange / Act
    const ics = icsContent(EVENT, 'appointment-2@linkcut', new Date('2026-10-20T12:00:00Z'))

    // Assert
    expect(ics).toContain('DTSTART:20261021T150000')
    expect(ics).toContain('DTEND:20261021T154500')
    expect(ics).toContain('LOCATION:Rua Augusta\\, 1500 – Consolação\\, São Paulo/SP')
    expect(ics).toContain('TRIGGER:-PT1H')
    expect(ics.split('\r\n')[0]).toBe('BEGIN:VCALENDAR')
  })
})
