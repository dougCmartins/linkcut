import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { BookingRequest, BookingRequestInput, Service } from './booking.model'
import type { BookedSlot } from './availability'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { initialRequests } from '@/mocks/barber'
import { useSettingsStore } from '@/Settings/settings.store'
import { recordCompletedService, removeCompletedService } from '@/History/history.api'
import { toIsoDate } from '@/utils/date'

export const useBookingStore = defineStore('booking', () => {
  const settingsStore = useSettingsStore()
  const requests = ref<BookingRequest[]>(initialRequests.map((request) => ({ ...request })))
  const isModalOpen = ref(false)

  const services = computed<Service[]>(() => settingsStore.schedule.services)

  const pendingRequests = computed(() =>
    requests.value.filter((request) => request.status === AppointmentStatus.Pending),
  )

  function findService(serviceId: string): Service | undefined {
    return services.value.find((service) => service.id === serviceId)
  }

  /** Pending and confirmed requests block their time slot. */
  const bookedSlots = computed<BookedSlot[]>(() =>
    requests.value
      .filter((request) => request.status !== AppointmentStatus.Rejected)
      .map((request) => ({
        date: request.date,
        time: request.time,
        durationMinutes: findService(request.serviceId)?.durationMinutes ?? 0,
      })),
  )

  function submitRequest(input: BookingRequestInput): BookingRequest {
    if (!findService(input.serviceId)) {
      throw new Error(`Unknown service: ${input.serviceId}`)
    }
    const request: BookingRequest = {
      ...input,
      id: `req-${Date.now()}-${requests.value.length + 1}`,
      status: AppointmentStatus.Pending,
    }
    requests.value.push(request)
    return request
  }

  function setStatus(requestId: string, status: AppointmentStatus): void {
    const request = requests.value.find((item) => item.id === requestId)
    if (!request) throw new Error(`Unknown booking request: ${requestId}`)
    request.status = status
  }

  const acceptRequest = (requestId: string) => setStatus(requestId, AppointmentStatus.Confirmed)
  const rejectRequest = (requestId: string) => setStatus(requestId, AppointmentStatus.Rejected)

  /** Today's accepted appointments (confirmed or already concluded), by time. */
  const todayAgenda = computed(() => {
    const today = toIsoDate(new Date())
    return requests.value
      .filter(
        (request) =>
          request.date === today &&
          (request.status === AppointmentStatus.Confirmed || request.status === AppointmentStatus.Completed),
      )
      .sort((a, b) => a.time.localeCompare(b.time))
  })

  function findRequest(requestId: string): BookingRequest {
    const request = requests.value.find((item) => item.id === requestId)
    if (!request) throw new Error(`Unknown booking request: ${requestId}`)
    return request
  }

  /**
   * "Concluir atendimento": only a confirmed appointment of today can be concluded.
   * Marks it completed and sends its value to the history (revenue).
   */
  async function completeRequest(requestId: string, now = new Date()): Promise<BookingRequest> {
    const request = findRequest(requestId)
    if (request.status !== AppointmentStatus.Confirmed || request.date !== toIsoDate(now)) {
      throw new Error(`Booking request ${requestId} cannot be concluded`)
    }
    const service = findService(request.serviceId)
    const priceInCents = service?.priceInCents ?? 0
    await recordCompletedService({
      id: request.id,
      date: request.date,
      time: request.time,
      clientName: request.clientName,
      serviceName: service?.name ?? '',
      priceInCents,
    })
    request.status = AppointmentStatus.Completed
    request.completedPriceInCents = priceInCents
    return request
  }

  /** Undo a conclusion made by mistake: back to confirmed and out of the history. */
  async function undoCompletion(requestId: string): Promise<void> {
    const request = findRequest(requestId)
    if (request.status !== AppointmentStatus.Completed) return
    await removeCompletedService(request.id)
    request.status = AppointmentStatus.Confirmed
    delete request.completedPriceInCents
  }

  const openModal = () => (isModalOpen.value = true)
  const closeModal = () => (isModalOpen.value = false)

  return {
    services,
    requests,
    isModalOpen,
    pendingRequests,
    todayAgenda,
    bookedSlots,
    findService,
    submitRequest,
    acceptRequest,
    rejectRequest,
    completeRequest,
    undoCompletion,
    openModal,
    closeModal,
  }
})
