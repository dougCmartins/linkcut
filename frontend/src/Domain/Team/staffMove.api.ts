import { ApiError } from '@/http/apiError'
import { http } from '@/http/client'
import type { StaffDayHours } from './staffSchedule.model'

/** Moving a barber to another unit of the brand (docs/decisoes/0003). snake_case on the wire. */

export type StaffMoveKind = 'transfer' | 'loan'

export interface MoveService {
  id: number
  name: string
}

/** What the form starts with: the destination's hours (a suggestion to edit) and its services. */
export interface MoveOptions {
  weeklyHours: StaffDayHours[]
  services: MoveService[]
}

export interface MoveInput {
  toUnitId: number
  kind: StaffMoveKind
  endsOn: string | null
  weeklyHours: StaffDayHours[]
  serviceIds: number[]
  permissions: 'keep' | 'reset'
}

/** A current move of the brand ("Emprestado para {filial} até {data}"). */
export interface BrandMove {
  id: number
  kind: StaffMoveKind
  startsOn: string
  endsOn: string | null
  barberName: string
  fromUnitId: number
  fromUnitName: string
  toUnitId: number
  toUnitName: string
}

/** A booking that keeps the barber where they are until it is passed to another barber. */
export interface BlockingAppointment {
  id: number
  date: string
  startsAt: string
  clientName: string
}

interface HourBody {
  weekday: number
  is_open: boolean
  opens_at: string
  closes_at: string
  break_starts_at?: string | null
  break_ends_at?: string | null
}

interface BrandMoveBody {
  id: number
  kind: StaffMoveKind
  starts_on: string
  ends_on: string | null
  barber_name: string
  from_unit_id: number
  from_unit_name: string
  to_unit_id: number
  to_unit_name: string
}

const toBrandMove = (body: BrandMoveBody): BrandMove => ({
  id: body.id,
  kind: body.kind,
  startsOn: body.starts_on,
  endsOn: body.ends_on,
  barberName: body.barber_name,
  fromUnitId: body.from_unit_id,
  fromUnitName: body.from_unit_name,
  toUnitId: body.to_unit_id,
  toUnitName: body.to_unit_name,
})

export async function fetchMoveOptions(staffId: number, toUnitId: number): Promise<MoveOptions> {
  const { data } = await http<{ weekly_hours: HourBody[]; services: MoveService[] }>(
    `/team/staff/${staffId}/move-options?to_unit_id=${toUnitId}`,
  )
  return {
    weeklyHours: data.weekly_hours.map((hour) => ({
      weekday: hour.weekday,
      isOpen: hour.is_open,
      opensAt: hour.opens_at,
      closesAt: hour.closes_at,
      breakStartsAt: hour.break_starts_at ?? null,
      breakEndsAt: hour.break_ends_at ?? null,
    })),
    services: data.services,
  }
}

export async function moveStaff(staffId: number, input: MoveInput): Promise<void> {
  await http(`/team/staff/${staffId}/move`, {
    method: 'POST',
    body: {
      to_unit_id: input.toUnitId,
      kind: input.kind,
      ends_on: input.kind === 'loan' ? input.endsOn : null,
      weekly_hours: input.weeklyHours.map((day) => ({
        weekday: day.weekday,
        is_open: day.isOpen,
        opens_at: day.opensAt,
        closes_at: day.closesAt,
        break_starts_at: day.isOpen ? day.breakStartsAt : null,
        break_ends_at: day.isOpen ? day.breakEndsAt : null,
      })),
      service_ids: input.serviceIds,
      permissions: input.permissions,
    },
  })
}

export async function fetchBrandMoves(): Promise<BrandMove[]> {
  const { data } = await http<BrandMoveBody[]>('/team/moves')
  return data.map(toBrandMove)
}

export async function extendLoan(moveId: number, endsOn: string): Promise<void> {
  await http(`/team/moves/${moveId}/extend`, { method: 'PATCH', body: { ends_on: endsOn } })
}

export async function endLoan(moveId: number): Promise<void> {
  await http(`/team/moves/${moveId}/end`, { method: 'POST' })
}

export async function makeLoanPermanent(moveId: number): Promise<void> {
  await http(`/team/moves/${moveId}/make-permanent`, { method: 'POST' })
}

/** The bookings listed by STAFF_HAS_UPCOMING_APPOINTMENTS (empty for any other error). */
export function blockingAppointments(caught: unknown): BlockingAppointment[] {
  if (!(caught instanceof ApiError) || caught.code !== 'STAFF_HAS_UPCOMING_APPOINTMENTS') return []
  const rows = (caught.data?.appointments ?? []) as { id: number; date: string; starts_at: string; client_name: string }[]
  return rows.map((row) => ({ id: row.id, date: row.date, startsAt: row.starts_at, clientName: row.client_name }))
}
