import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAgendaStore } from '../agenda.store'
import { TODAY, installFakeScheduleApi, requestRow } from './fakeScheduleApi'
import { AppointmentStatus } from '@/types/appointmentStatus'

describe('agenda store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('loads the pending requests and today agenda from the API', async () => {
    // Arrange
    installFakeScheduleApi()
    const store = useAgendaStore()

    // Act
    await store.load()

    // Assert
    expect(store.pendingRequests.map((request) => [request.clientName, request.time])).toEqual([
      ['Bruno Lima', '10:00'],
      ['Pedro Martins', '16:30'],
    ])
    expect(store.pendingRequests[0]).toMatchObject({ id: 1, serviceId: '2', serviceName: 'Barba', durationMinutes: 30 })
    expect(store.todayAgenda.map((request) => request.clientName)).toEqual(['Lucas Andrade'])
  })

  it('accepting a request of today moves it to the agenda', async () => {
    // Arrange
    installFakeScheduleApi({ requests: [requestRow({ id: 7, date: TODAY, starts_at: '11:00' })] })
    const store = useAgendaStore()
    await store.load()

    // Act
    await store.accept(7)

    // Assert
    expect(store.pendingRequests).toEqual([])
    expect(store.todayAgenda.map((request) => [request.id, request.status])).toEqual([[7, AppointmentStatus.Confirmed]])
  })

  it('rejecting takes it out of the pending list', async () => {
    // Arrange
    const api = installFakeScheduleApi()
    const store = useAgendaStore()
    await store.load()

    // Act
    await store.reject(1)

    // Assert
    expect(store.pendingRequests.map((request) => request.id)).toEqual([2])
    expect(api.requests.find((row) => row.id === 1)?.status).toBe('rejected')
  })

  it('concludes with the price the API recorded and undoes it', async () => {
    // Arrange
    installFakeScheduleApi()
    const store = useAgendaStore()
    await store.load()

    // Act
    const completed = await store.complete(3)
    const afterComplete = store.todayAgenda[0]?.status
    await store.undoCompletion(3)

    // Assert
    expect(completed).toMatchObject({ status: AppointmentStatus.Completed, completedPriceInCents: 5000 })
    expect(afterComplete).toBe(AppointmentStatus.Completed)
    expect(store.todayAgenda[0]?.status).toBe(AppointmentStatus.Confirmed)
  })

  it('rejects with the API code when the request was already answered', async () => {
    // Arrange
    const api = installFakeScheduleApi()
    const store = useAgendaStore()
    await store.load()
    api.requests[0]!.status = 'rejected'

    // Act
    const accepting = store.accept(1)

    // Assert
    await expect(accepting).rejects.toMatchObject({ code: 'INVALID_STATUS_TRANSITION' })
  })
})
