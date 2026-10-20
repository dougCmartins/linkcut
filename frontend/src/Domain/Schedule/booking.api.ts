import type { AvailableDay, BookingConfirmation, BookingRequest, BookingRequestInput } from './booking.model'
import { http } from '@/http/client'
import type { AppointmentStatus } from '@/types/appointmentStatus'

/** Schedule API (snake_case on the wire, camelCase in the SPA). */

interface BookingRequestBody {
  id: number
  client_name: string
  client_whatsapp: string
  service_id: number
  service_name: string
  duration_minutes: number
  date: string
  starts_at: string
  ends_at: string
  status: AppointmentStatus
  client_distance_meters: number | null
}

const toRequest = (body: BookingRequestBody): BookingRequest => ({
  id: body.id,
  clientName: body.client_name,
  clientWhatsapp: body.client_whatsapp,
  serviceId: String(body.service_id),
  serviceName: body.service_name,
  durationMinutes: body.duration_minutes,
  date: body.date,
  time: body.starts_at,
  status: body.status,
  clientDistanceMeters: body.client_distance_meters ?? null,
})

const publicPath = (slug: string) => `/public/${encodeURIComponent(slug)}`

/* Visitor (no session) */

/** Free times of every day of the booking window, for a service (or the shortest one). */
export async function fetchAvailability(slug: string, serviceId: string | null): Promise<AvailableDay[]> {
  const query = serviceId ? `?service_id=${encodeURIComponent(serviceId)}` : ''
  const { data } = await http<{ days: AvailableDay[] }>(`${publicPath(slug)}/availability${query}`, { auth: false })
  return data.days
}

/** Rejects with SLOT_UNAVAILABLE (409) when the time was taken in the meantime. */
export async function createBookingRequest(slug: string, input: BookingRequestInput): Promise<BookingConfirmation> {
  const { data } = await http<{ id: number; service_name: string; date: string; time: string }>(
    `${publicPath(slug)}/booking-requests`,
    {
      method: 'POST',
      auth: false,
      body: {
        client_name: input.clientName,
        client_whatsapp: input.clientWhatsapp,
        service_id: Number(input.serviceId),
        date: input.date,
        time: input.time,
        client_latitude: input.clientLocation?.latitude ?? null,
        client_longitude: input.clientLocation?.longitude ?? null,
      },
    },
  )
  return { id: data.id, serviceName: data.service_name, date: data.date, time: data.time }
}

/* Barber */

export async function fetchPendingRequests(): Promise<BookingRequest[]> {
  const { data } = await http<BookingRequestBody[]>('/schedule/requests?status=pending')
  return data.map(toRequest)
}

/** Today's confirmed and concluded appointments, by time (today in the barbershop's timezone). */
export async function fetchTodayAgenda(): Promise<BookingRequest[]> {
  const { data } = await http<BookingRequestBody[]>('/schedule/today')
  return data.map(toRequest)
}

export async function acceptBookingRequest(id: number): Promise<BookingRequest> {
  const { data } = await http<BookingRequestBody>(`/schedule/requests/${id}/accept`, { method: 'PATCH' })
  return toRequest(data)
}

export async function rejectBookingRequest(id: number): Promise<BookingRequest> {
  const { data } = await http<BookingRequestBody>(`/schedule/requests/${id}/reject`, { method: 'PATCH' })
  return toRequest(data)
}

/** Concludes and puts the service's current price (and cost) in the revenue. */
export async function completeBookingRequest(id: number): Promise<BookingRequest> {
  const { data } = await http<{ request: BookingRequestBody; price_in_cents: number }>(`/schedule/requests/${id}/complete`, {
    method: 'POST',
  })
  return { ...toRequest(data.request), completedPriceInCents: data.price_in_cents }
}

/** Undo of a conclusion: back to confirmed and out of the revenue. */
export async function reopenBookingRequest(id: number): Promise<BookingRequest> {
  const { data } = await http<BookingRequestBody>(`/schedule/requests/${id}/reopen`, { method: 'POST' })
  return toRequest(data)
}
