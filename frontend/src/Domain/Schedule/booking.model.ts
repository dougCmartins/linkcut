import type { AppointmentStatus } from '@/types/appointmentStatus'

export type { Service } from '@/Domain/Settings/settings.model'

/** A client's request for a time, as the barber sees it. */
export interface BookingRequest {
  id: number
  clientName: string
  /** Digits only, with area code. */
  clientWhatsapp: string
  serviceId: string
  /** Name and length at request time (kept if the service changes later). */
  serviceName: string
  durationMinutes: number
  /** Local calendar date of the barbershop, "YYYY-MM-DD". */
  date: string
  /** Start, "HH:mm". */
  time: string
  status: AppointmentStatus
  /** How far the client was from the barbershop when booking (null = not shared). */
  clientDistanceMeters: number | null
  /** Price that went into the revenue when it was concluded. */
  completedPriceInCents?: number
}

/** What the client sends from the booking modal. */
export interface BookingRequestInput {
  clientName: string
  clientWhatsapp: string
  /** Gets the answer and the reminder; also the client's account (client area). */
  clientEmail: string
  serviceId: string
  date: string
  time: string
  /** Only when the client shared the location (used to compute the distance; never stored). */
  clientLocation?: { latitude: number; longitude: number } | null
}

/** A day of the booking window and its free start times ("HH:mm"). */
export interface AvailableDay {
  date: string
  times: string[]
}

/** The API's answer to the client: the request is waiting for the barber. */
export interface BookingConfirmation {
  id: number
  serviceName: string
  date: string
  time: string
}
