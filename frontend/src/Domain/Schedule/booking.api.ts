import type { AvailableDay, BookingConfirmation, BookingRequest, BookingRequestInput, PublicStaff } from './booking.model'
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
  service_ids?: number[] | null
  price_in_cents?: number | null
  client_notes?: string | null
  staff_id?: number | null
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
  serviceIds: (body.service_ids ?? [body.service_id]).map(String),
  priceInCents: body.price_in_cents ?? null,
  clientNotes: body.client_notes ?? null,
  staffId: body.staff_id ?? null,
})

const publicPath = (slug: string) => `/public/${encodeURIComponent(slug)}`

/* Visitor (no session) */

const serviceQuery = (serviceIds: string[]) => serviceIds.map((id) => `service_ids[]=${encodeURIComponent(id)}`)

/** Active barbers of the unit; with services, only those who do all of them. */
export async function fetchPublicStaff(slug: string, serviceIds: string[] = []): Promise<PublicStaff[]> {
  const query = serviceIds.length ? `?${serviceQuery(serviceIds).join('&')}` : ''
  const { data } = await http<PublicStaff[]>(`${publicPath(slug)}/staff${query}`, { auth: false })
  return data.map((member) => ({ id: member.id, name: member.name, avatar: member.avatar ?? null }))
}

/**
 * Free times of every day of the booking window, for the chosen services together (or the shortest one),
 * of one barber or of any of them (no staffId).
 */
export async function fetchAvailability(slug: string, serviceIds: string[], staffId: number | null = null): Promise<AvailableDay[]> {
  const params = [...serviceQuery(serviceIds), ...(staffId !== null ? [`staff_id=${staffId}`] : [])]
  const query = params.length ? `?${params.join('&')}` : ''
  const { data } = await http<{ days: AvailableDay[] }>(`${publicPath(slug)}/availability${query}`, { auth: false })
  return data.days
}

/** Rejects with SLOT_UNAVAILABLE (409) when the time was taken in the meantime. */
export async function createBookingRequest(slug: string, input: BookingRequestInput): Promise<BookingConfirmation> {
  const { data } = await http<{
    id: number
    service_name: string
    date: string
    time: string
    push_url?: string | null
    staff_name?: string | null
  }>(
    `${publicPath(slug)}/booking-requests`,
    {
      method: 'POST',
      auth: false,
      body: {
        client_name: input.clientName,
        client_whatsapp: input.clientWhatsapp,
        client_email: input.clientEmail,
        service_ids: input.serviceIds.map(Number),
        client_notes: input.clientNotes?.trim() || null,
        date: input.date,
        time: input.time,
        client_latitude: input.clientLocation?.latitude ?? null,
        client_longitude: input.clientLocation?.longitude ?? null,
        ...(input.staffId != null ? { staff_id: input.staffId } : {}),
      },
    },
  )
  return {
    id: data.id,
    serviceName: data.service_name,
    date: data.date,
    time: data.time,
    pushUrl: data.push_url ?? null,
    staffName: data.staff_name ?? null,
  }
}

/* Barber */

export async function fetchPendingRequests(): Promise<BookingRequest[]> {
  const { data } = await http<BookingRequestBody[]>('/schedule/requests?status=pending')
  return data.map(toRequest)
}

/** Confirmed appointments from today on, by date and time (the API caps the list). */
export async function fetchConfirmedAppointments(): Promise<BookingRequest[]> {
  const { data } = await http<BookingRequestBody[]>('/schedule/requests?status=confirmed')
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

/** Owner only: moves the appointment to another barber; SLOT_UNAVAILABLE (409) when they are not free then. */
export async function reassignBookingRequest(id: number, staffId: number): Promise<BookingRequest> {
  const { data } = await http<BookingRequestBody>(`/schedule/requests/${id}/reassign`, { method: 'POST', body: { staff_id: staffId } })
  return toRequest(data)
}
