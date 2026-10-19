import type { AppointmentStatus } from '@/types/appointmentStatus'

export interface Service {
  id: string
  name: string
  durationMinutes: number
  priceInCents: number
  /** Fixed cost per procedure (material, products), private to the barber. Missing = no cost. */
  costInCents?: number
}

export interface BookingRequest {
  id: string
  clientName: string
  clientWhatsapp: string
  serviceId: string
  /** Local calendar date, "YYYY-MM-DD". */
  date: string
  /** Local time, "HH:mm". */
  time: string
  status: AppointmentStatus
  /** Price charged when the appointment was concluded (snapshot). */
  completedPriceInCents?: number
}

export interface BookingRequestInput {
  clientName: string
  clientWhatsapp: string
  serviceId: string
  date: string
  time: string
}
