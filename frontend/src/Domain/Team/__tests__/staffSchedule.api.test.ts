import { afterEach, describe, expect, it, vi } from 'vitest'
import { createStaffBlock, fetchStaffHours, saveStaffHours, saveStaffServices } from '../staffSchedule.api'
import { installFakeApi } from '@/utils/testFetch'

describe('staffSchedule.api', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('reads the hours with the break in camelCase', async () => {
    // Arrange
    installFakeApi({
      'GET /team/staff/:id/hours': () => ({
        status: 200,
        body: [{ weekday: 1, is_open: true, opens_at: '09:00', closes_at: '18:00', break_starts_at: '12:00', break_ends_at: '13:00' }],
      }),
    })

    // Act
    const hours = await fetchStaffHours(5)

    // Assert
    expect(hours).toEqual([{ weekday: 1, isOpen: true, opensAt: '09:00', closesAt: '18:00', breakStartsAt: '12:00', breakEndsAt: '13:00' }])
  })

  it('saves the hours in snake_case and drops the break of a closed day', async () => {
    // Arrange
    const { calls } = installFakeApi({ 'PUT /team/staff/:id/hours': () => ({ status: 200, body: [] }) })

    // Act
    await saveStaffHours(5, [
      { weekday: 1, isOpen: true, opensAt: '09:00', closesAt: '18:00', breakStartsAt: '12:00', breakEndsAt: '13:00' },
      { weekday: 0, isOpen: false, opensAt: '09:00', closesAt: '18:00', breakStartsAt: '12:00', breakEndsAt: '13:00' },
    ])

    // Assert
    expect(calls[0]?.path).toBe('/team/staff/5/hours')
    expect(calls[0]?.body).toEqual({
      weekly_hours: [
        { weekday: 1, is_open: true, opens_at: '09:00', closes_at: '18:00', break_starts_at: '12:00', break_ends_at: '13:00' },
        { weekday: 0, is_open: false, opens_at: '09:00', closes_at: '18:00', break_starts_at: null, break_ends_at: null },
      ],
    })
  })

  it('saves the services and reads them back', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'PUT /team/staff/:id/services': ({ body }) => ({ status: 200, body: { service_ids: body?.service_ids } }),
    })

    // Act
    const saved = await saveStaffServices(5, [1, 2])

    // Assert
    expect(calls[0]?.body).toEqual({ service_ids: [1, 2] })
    expect(saved).toEqual([1, 2])
  })

  it('a weekly block sends its weekdays; a single one sends none', async () => {
    // Arrange
    const { calls } = installFakeApi({
      'POST /team/staff/:id/blocks': ({ body }) => ({ status: 201, body: { id: 9, staff_id: 5, ...body } }),
    })

    // Act
    const weekly = await createStaffBlock(5, {
      startsAt: '2026-11-26 13:00',
      endsAt: '2026-11-26 18:00',
      recurrence: 'weekly',
      weekdays: [3],
      note: ' Curso ',
    })
    await createStaffBlock(5, { startsAt: '2026-11-27 09:00', endsAt: '2026-11-27 10:00', recurrence: null, weekdays: [1], note: '' })

    // Assert
    expect(calls[0]?.body).toEqual({ starts_at: '2026-11-26 13:00', ends_at: '2026-11-26 18:00', recurrence: 'weekly', weekdays: [3], note: 'Curso' })
    expect(calls[1]?.body).toEqual({ starts_at: '2026-11-27 09:00', ends_at: '2026-11-27 10:00', recurrence: null, weekdays: null, note: null })
    expect(weekly).toMatchObject({ id: 9, startsAt: '2026-11-26 13:00', recurrence: 'weekly', weekdays: [3] })
  })
})
