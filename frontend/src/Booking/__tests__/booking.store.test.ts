import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useBookingStore } from '../booking.store'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { installFakeSettingsApi } from '@/Domain/Settings/__tests__/fakeSettingsApi'

const validInput = {
  clientName: 'João Silva',
  clientWhatsapp: '11912345678',
  serviceId: '1',
  date: '2026-10-20',
  time: '10:00',
}

describe('booking store', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    installFakeSettingsApi()
    await useSettingsStore().ensureLoaded()
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('creates the simulated requests with the services of the barbershop', () => {
    // Arrange & Act
    const store = useBookingStore()

    // Assert
    expect(store.pendingRequests.map((request) => request.serviceId)).toEqual(['1', '2', '1'])
  })

  it('creates every new request as pending', () => {
    // Arrange
    const store = useBookingStore()
    const pendingBefore = store.pendingRequests.length

    // Act
    const request = store.submitRequest(validInput)

    // Assert
    expect(request.status).toBe(AppointmentStatus.Pending)
    expect(store.pendingRequests).toHaveLength(pendingBefore + 1)
  })

  it('confirms a request when the barber accepts it', () => {
    // Arrange
    const store = useBookingStore()
    const request = store.submitRequest(validInput)

    // Act
    store.acceptRequest(request.id)

    // Assert
    expect(store.requests.find((item) => item.id === request.id)?.status).toBe(AppointmentStatus.Confirmed)
    expect(store.pendingRequests.some((item) => item.id === request.id)).toBe(false)
  })

  it('marks a request as rejected when the barber declines it', () => {
    // Arrange
    const store = useBookingStore()
    const request = store.submitRequest(validInput)

    // Act
    store.rejectRequest(request.id)

    // Assert
    expect(store.requests.find((item) => item.id === request.id)?.status).toBe(AppointmentStatus.Rejected)
  })

  describe('concluding an appointment (today agenda)', () => {
    const NOW = new Date(2026, 9, 20, 18, 0)

    beforeEach(() => {
      vi.useFakeTimers({ toFake: ['Date'] })
      vi.setSystemTime(NOW)
    })

    afterEach(() => {
      vi.useRealTimers()
    })

    function confirmedToday(store: ReturnType<typeof useBookingStore>, time = '10:00') {
      const request = store.submitRequest({ ...validInput, time })
      store.acceptRequest(request.id)
      return request
    }

    it("lists only today's confirmed or concluded appointments, by time", () => {
      // Arrange
      const store = useBookingStore()
      const late = confirmedToday(store, '17:00')
      const early = confirmedToday(store, '09:00')
      store.submitRequest({ ...validInput, time: '11:00' }) // still pending
      const tomorrow = store.submitRequest({ ...validInput, date: '2026-10-21' })
      store.acceptRequest(tomorrow.id)

      // Act
      const agendaIds = store.todayAgenda.map((request) => request.id)

      // Assert
      expect(agendaIds).toEqual([early.id, late.id])
    })

    it('marks it completed with the price of the service', async () => {
      // Arrange
      const store = useBookingStore()
      const request = confirmedToday(store)

      // Act
      const completed = await store.completeRequest(request.id, NOW)

      // Assert
      expect(completed.status).toBe(AppointmentStatus.Completed)
      expect(completed.completedPriceInCents).toBe(5000)
    })

    it('refuses to conclude a pending request or one from another day', async () => {
      // Arrange
      const store = useBookingStore()
      const pending = store.submitRequest(validInput)
      const tomorrow = store.submitRequest({ ...validInput, date: '2026-10-21' })
      store.acceptRequest(tomorrow.id)

      // Act
      const completePending = store.completeRequest(pending.id, NOW)
      const completeTomorrow = store.completeRequest(tomorrow.id, NOW)

      // Assert
      await expect(completePending).rejects.toThrow('cannot be concluded')
      await expect(completeTomorrow).rejects.toThrow('cannot be concluded')
    })

    it('undo puts it back to confirmed', async () => {
      // Arrange
      const store = useBookingStore()
      const request = confirmedToday(store)
      await store.completeRequest(request.id, NOW)

      // Act
      await store.undoCompletion(request.id)

      // Assert
      const stored = store.requests.find((item) => item.id === request.id)
      expect(stored?.status).toBe(AppointmentStatus.Confirmed)
      expect(stored?.completedPriceInCents).toBeUndefined()
    })
  })
})
