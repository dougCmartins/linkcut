import type { BookingRequest } from '@/Booking/booking.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { nextDays, toIsoDate } from '@/utils/date'

/** Mock operational data until the backend exists. Profile, portfolio and schedule now live in Settings. */

const [today, tomorrow] = nextDays(2)

export const initialRequests: BookingRequest[] = [
  {
    id: 'req-1',
    clientName: 'Lucas Andrade',
    clientWhatsapp: '11988887777',
    serviceId: 'haircut',
    date: toIsoDate(today ?? new Date()),
    time: '14:30',
    status: AppointmentStatus.Pending,
  },
  {
    id: 'req-2',
    clientName: 'Bruno Lima',
    clientWhatsapp: '11977776666',
    serviceId: 'beard',
    date: toIsoDate(tomorrow ?? new Date()),
    time: '10:00',
    status: AppointmentStatus.Pending,
  },
  {
    id: 'req-3',
    clientName: 'Pedro Martins',
    clientWhatsapp: '11966665555',
    serviceId: 'haircut',
    date: toIsoDate(tomorrow ?? new Date()),
    time: '16:30',
    status: AppointmentStatus.Pending,
  },
]

/** Current month figures, in cents. */
export const monthlyFinances = {
  revenueInCents: 1_284_000,
  expensesInCents: 846_000,
}
