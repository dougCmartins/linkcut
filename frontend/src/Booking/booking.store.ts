import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { BookingRequest, BookingRequestInput, Service } from './booking.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { initialRequests, services as mockServices } from '@/mocks/barber'

export const useBookingStore = defineStore('booking', () => {
  const services = ref<Service[]>(mockServices)
  const requests = ref<BookingRequest[]>(initialRequests.map((request) => ({ ...request })))
  const isModalOpen = ref(false)

  const pendingRequests = computed(() =>
    requests.value.filter((request) => request.status === AppointmentStatus.Pending),
  )

  function findService(serviceId: string): Service | undefined {
    return services.value.find((service) => service.id === serviceId)
  }

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

  const openModal = () => (isModalOpen.value = true)
  const closeModal = () => (isModalOpen.value = false)

  return {
    services,
    requests,
    isModalOpen,
    pendingRequests,
    findService,
    submitRequest,
    acceptRequest,
    rejectRequest,
    openModal,
    closeModal,
  }
})
