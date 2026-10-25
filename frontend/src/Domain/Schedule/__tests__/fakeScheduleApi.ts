import { envelope, installFakeApi, type FakeCall, type FakeHandler } from '@/utils/testFetch'

/**
 * A fake Schedule API with the same contract as the backend: public free times and requests of SLUG,
 * the barber's pending requests and today's agenda, status transitions (409 when not allowed).
 */

export const SLUG = 'rafael'
export const TODAY = '2026-10-19'

export interface RequestRow {
  id: number
  client_name: string
  client_whatsapp: string
  service_id: number
  service_name: string
  duration_minutes: number
  date: string
  starts_at: string
  ends_at: string
  status: 'pending' | 'confirmed' | 'rejected' | 'completed'
  client_distance_meters: number | null
  service_ids?: number[]
  price_in_cents?: number | null
  client_notes?: string | null
}

/** Corte (id 1, 45 min, R$ 50) and Barba (id 2, 30 min, R$ 40), as in the Settings fixture. */
const SERVICES: Record<number, { name: string; duration: number; price: number; cost: number }> = {
  1: { name: 'Corte', duration: 45, price: 5000, cost: 600 },
  2: { name: 'Barba', duration: 30, price: 4000, cost: 400 },
}

const addMinutes = (time: string, minutes: number) => {
  const [hours = 0, mins = 0] = time.split(':').map(Number)
  const total = hours * 60 + mins + minutes
  return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`
}

const addDays = (date: string, days: number) => {
  const day = new Date(`${date}T12:00`)
  day.setDate(day.getDate() + days)
  return `${day.getFullYear()}-${String(day.getMonth() + 1).padStart(2, '0')}-${String(day.getDate()).padStart(2, '0')}`
}

export function requestRow(overrides: Partial<RequestRow> & Pick<RequestRow, 'id'>): RequestRow {
  const serviceId = overrides.service_id ?? 1
  const service = SERVICES[serviceId]!
  const startsAt = overrides.starts_at ?? '10:00'
  return {
    client_name: 'Lucas Andrade',
    client_whatsapp: '11988887777',
    service_id: serviceId,
    service_name: service.name,
    duration_minutes: service.duration,
    date: addDays(TODAY, 1),
    starts_at: startsAt,
    ends_at: addMinutes(startsAt, service.duration),
    status: 'pending',
    client_distance_meters: null,
    ...overrides,
  }
}

/** Two pending requests for tomorrow and one confirmed for today. */
export function createRequests(): RequestRow[] {
  return [
    requestRow({ id: 1, client_name: 'Bruno Lima', service_id: 2, starts_at: '10:00' }),
    requestRow({ id: 2, client_name: 'Pedro Martins', starts_at: '16:30' }),
    requestRow({ id: 3, client_name: 'Lucas Andrade', date: TODAY, starts_at: '18:00', status: 'confirmed' }),
  ]
}

export interface ScheduleOptions {
  today?: string
  requests?: RequestRow[]
  /** Free times of each open day (Sundays are closed). */
  dayTimes?: string[]
  /** Called when an appointment is concluded or reopened (e.g. to move the fake revenue). */
  onComplete?: (row: RequestRow, priceInCents: number, costInCents: number) => void
  onReopen?: (row: RequestRow) => void
}

export function createScheduleRoutes(options: ScheduleOptions = {}) {
  const today = options.today ?? TODAY
  const requests = options.requests ?? createRequests()
  const dayTimes = options.dayTimes ?? ['09:00', '10:00', '11:00', '14:00', '15:00']
  let nextId = 100
  const blocking = () => requests.filter((row) => row.status !== 'rejected')
  const find = (call: FakeCall) => requests.find((row) => row.id === Number(call.params.id))

  function transition(call: FakeCall, from: RequestRow['status'], to: RequestRow['status']) {
    const row = find(call)
    if (!row) return { row: null, error: envelope('BOOKING_REQUEST_NOT_FOUND', 404) }
    if (row.status !== from) return { row: null, error: envelope('INVALID_STATUS_TRANSITION', 409) }
    row.status = to
    return { row, error: null }
  }

  const routes: Record<string, FakeHandler> = {
    [`GET /public/${SLUG}/availability`]: ({ query }: FakeCall) => {
      const serviceId = Number(query.getAll('service_ids[]')[0] ?? 2)
      const days = Array.from({ length: 7 }, (_, index) => {
        const date = addDays(today, index)
        const isSunday = new Date(`${date}T12:00`).getDay() === 0
        const taken = blocking()
          .filter((row) => row.date === date)
          .map((row) => row.starts_at)
        return { date, times: isSunday ? [] : dayTimes.filter((time) => !taken.includes(time)) }
      })
      return { status: 200, body: { service_id: serviceId, days } }
    },
    [`POST /public/${SLUG}/booking-requests`]: ({ body }: FakeCall) => {
      const input = body as {
        client_name: string
        client_whatsapp: string
        client_email: string
        service_ids: number[]
        client_notes: string | null
        date: string
        time: string
        client_latitude: number | null
        client_longitude: number | null
      }
      if (!/^\S+@\S+\.\S+$/.test(input.client_email ?? '')) return envelope('VALIDATION_FAILED', 422)
      if (blocking().some((row) => row.date === input.date && row.starts_at === input.time)) return envelope('SLOT_UNAVAILABLE', 409)
      const row = requestRow({
        id: nextId++,
        client_name: input.client_name,
        client_whatsapp: input.client_whatsapp,
        service_id: input.service_ids[0],
        date: input.date,
        starts_at: input.time,
        // The real API measures it from the barbershop; any shared location is 3.2 km away here.
        client_distance_meters: input.client_latitude === null ? null : 3200,
      })
      requests.push(row)
      return { status: 201, body: { id: row.id, service_name: row.service_name, date: row.date, time: row.starts_at, status: 'pending' } }
    },
    'GET /schedule/requests': (call: FakeCall) => ({
      status: 200,
      body: requests
        .filter((row) => row.status === (call.query.get('status') ?? 'pending') && row.date >= today)
        .sort((a, b) => `${a.date} ${a.starts_at}`.localeCompare(`${b.date} ${b.starts_at}`)),
    }),
    'GET /schedule/today': () => ({
      status: 200,
      body: requests
        .filter((row) => row.date === today && (row.status === 'confirmed' || row.status === 'completed'))
        .sort((a, b) => a.starts_at.localeCompare(b.starts_at)),
    }),
    'PATCH /schedule/requests/:id/accept': (call: FakeCall) => {
      const { row, error } = transition(call, 'pending', 'confirmed')
      return error ?? { status: 200, body: row }
    },
    'PATCH /schedule/requests/:id/reject': (call: FakeCall) => {
      const { row, error } = transition(call, 'pending', 'rejected')
      return error ?? { status: 200, body: row }
    },
    'POST /schedule/requests/:id/complete': (call: FakeCall) => {
      if (find(call)?.date !== today)
        return find(call) ? envelope('INVALID_STATUS_TRANSITION', 409) : envelope('BOOKING_REQUEST_NOT_FOUND', 404)
      const { row, error } = transition(call, 'confirmed', 'completed')
      if (error || !row) return error!
      const service = SERVICES[row.service_id]!
      options.onComplete?.(row, service.price, service.cost)
      return { status: 201, body: { request: row, price_in_cents: service.price, cost_in_cents: service.cost } }
    },
    'POST /schedule/requests/:id/reopen': (call: FakeCall) => {
      const { row, error } = transition(call, 'completed', 'confirmed')
      if (error || !row) return error!
      options.onReopen?.(row)
      return { status: 201, body: row }
    },
  }

  return { routes, requests }
}

export function installFakeScheduleApi(options: ScheduleOptions = {}, extraRoutes: Record<string, FakeHandler> = {}) {
  const schedule = createScheduleRoutes(options)
  return { ...installFakeApi({ ...schedule.routes, ...extraRoutes }), requests: schedule.requests }
}
