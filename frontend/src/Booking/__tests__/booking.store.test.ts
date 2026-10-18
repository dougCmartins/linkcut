import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useBookingStore } from '../booking.store'
import { AppointmentStatus } from '@/types/appointmentStatus'

const validInput = {
  clientName: 'João Silva',
  clientWhatsapp: '11912345678',
  serviceId: 'haircut',
  date: '2026-10-20',
  time: '10:00',
}

describe('booking store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
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

  it('rejects a request for an unknown service', () => {
    // Arrange
    const store = useBookingStore()

    // Act
    const submit = () => store.submitRequest({ ...validInput, serviceId: 'unknown' })

    // Assert
    expect(submit).toThrow('Unknown service')
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
})
