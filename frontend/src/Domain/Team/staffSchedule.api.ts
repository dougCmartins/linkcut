import { http } from '@/http/client'
import type { NewStaffTimeBlock, StaffDayHours, StaffTimeBlock } from './staffSchedule.model'

/** A barber's hours, services and blocks (owner only; snake_case on the wire). */

interface HourBody {
  weekday: number
  is_open: boolean
  opens_at: string
  closes_at: string
  break_starts_at?: string | null
  break_ends_at?: string | null
}

interface BlockBody {
  id: number
  starts_at: string
  ends_at: string
  recurrence: 'weekly' | null
  weekdays: number[] | null
  note: string | null
}

const base = (staffId: number) => `/team/staff/${staffId}`

const toHours = (body: HourBody): StaffDayHours => ({
  weekday: body.weekday,
  isOpen: body.is_open,
  opensAt: body.opens_at,
  closesAt: body.closes_at,
  breakStartsAt: body.break_starts_at ?? null,
  breakEndsAt: body.break_ends_at ?? null,
})

const toBlock = (body: BlockBody): StaffTimeBlock => ({
  id: body.id,
  startsAt: body.starts_at,
  endsAt: body.ends_at,
  recurrence: body.recurrence,
  weekdays: body.weekdays,
  note: body.note,
})

export async function fetchStaffHours(staffId: number): Promise<StaffDayHours[]> {
  const { data } = await http<HourBody[]>(`${base(staffId)}/hours`)
  return data.map(toHours)
}

export async function saveStaffHours(staffId: number, days: StaffDayHours[]): Promise<StaffDayHours[]> {
  const { data } = await http<HourBody[]>(`${base(staffId)}/hours`, {
    method: 'PUT',
    body: {
      weekly_hours: days.map((day) => ({
        weekday: day.weekday,
        is_open: day.isOpen,
        opens_at: day.opensAt,
        closes_at: day.closesAt,
        break_starts_at: day.isOpen ? day.breakStartsAt : null,
        break_ends_at: day.isOpen ? day.breakEndsAt : null,
      })),
    },
  })
  return data.map(toHours)
}

export async function fetchStaffServices(staffId: number): Promise<number[]> {
  const { data } = await http<{ service_ids: number[] }>(`${base(staffId)}/services`)
  return data.service_ids
}

export async function saveStaffServices(staffId: number, serviceIds: number[]): Promise<number[]> {
  const { data } = await http<{ service_ids: number[] }>(`${base(staffId)}/services`, {
    method: 'PUT',
    body: { service_ids: serviceIds },
  })
  return data.service_ids
}

export async function fetchStaffBlocks(staffId: number): Promise<StaffTimeBlock[]> {
  const { data } = await http<BlockBody[]>(`${base(staffId)}/blocks`)
  return data.map(toBlock)
}

export async function createStaffBlock(staffId: number, block: NewStaffTimeBlock): Promise<StaffTimeBlock> {
  const { data } = await http<BlockBody>(`${base(staffId)}/blocks`, {
    method: 'POST',
    body: {
      starts_at: block.startsAt,
      ends_at: block.endsAt,
      recurrence: block.recurrence,
      weekdays: block.recurrence === 'weekly' ? block.weekdays : null,
      note: block.note?.trim() || null,
    },
  })
  return toBlock(data)
}

export async function deleteStaffBlock(staffId: number, blockId: number): Promise<void> {
  await http<null>(`${base(staffId)}/blocks/${blockId}`, { method: 'DELETE' })
}
