import type { BookingRequest } from '@/Booking/booking.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { nextDays, toIsoDate } from '@/utils/date'

/** Simulated booking requests until the Schedule API (stage 3). They use the barbershop's real service ids. */
export function createMockRequests(serviceIds: string[]): BookingRequest[] {
  const [today, tomorrow] = nextDays(2)
  const serviceAt = (index: number) => serviceIds[index % serviceIds.length] ?? ''
  return [
    {
      id: 'req-1',
      clientName: 'Lucas Andrade',
      clientWhatsapp: '11988887777',
      serviceId: serviceAt(0),
      date: toIsoDate(today ?? new Date()),
      time: '14:30',
      status: AppointmentStatus.Pending,
    },
    {
      id: 'req-2',
      clientName: 'Bruno Lima',
      clientWhatsapp: '11977776666',
      serviceId: serviceAt(1),
      date: toIsoDate(tomorrow ?? new Date()),
      time: '10:00',
      status: AppointmentStatus.Pending,
    },
    {
      id: 'req-3',
      clientName: 'Pedro Martins',
      clientWhatsapp: '11966665555',
      serviceId: serviceAt(0),
      date: toIsoDate(tomorrow ?? new Date()),
      time: '16:30',
      status: AppointmentStatus.Pending,
    },
  ]
}
