import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { BookingRequest } from './booking.model'
import {
  acceptBookingRequest,
  completeBookingRequest,
  fetchPendingRequests,
  fetchTodayAgenda,
  rejectBookingRequest,
  reopenBookingRequest,
} from './booking.api'

/** The barber's side of the agenda (dashboard): pending requests and today's appointments, from the API. */
export const useAgendaStore = defineStore('agenda', () => {
  const pendingRequests = ref<BookingRequest[]>([])
  const todayAgenda = ref<BookingRequest[]>([])
  const isLoading = ref(false)
  const hasFailed = ref(false)

  async function load(): Promise<void> {
    isLoading.value = true
    hasFailed.value = false
    try {
      ;[pendingRequests.value, todayAgenda.value] = await Promise.all([fetchPendingRequests(), fetchTodayAgenda()])
    } catch {
      hasFailed.value = true
    } finally {
      isLoading.value = false
    }
  }

  /** Replaces an appointment of today's agenda with its saved version. */
  function replaceInToday(request: BookingRequest): void {
    todayAgenda.value = todayAgenda.value.map((item) => (item.id === request.id ? request : item))
  }

  async function accept(id: number): Promise<void> {
    await acceptBookingRequest(id)
    pendingRequests.value = pendingRequests.value.filter((item) => item.id !== id)
    // Reloaded rather than guessed: only the API knows the barbershop's "today".
    todayAgenda.value = await fetchTodayAgenda()
  }

  async function reject(id: number): Promise<void> {
    await rejectBookingRequest(id)
    pendingRequests.value = pendingRequests.value.filter((item) => item.id !== id)
  }

  /** "Concluir atendimento": the price goes into the revenue on the API. */
  async function complete(id: number): Promise<BookingRequest> {
    const completed = await completeBookingRequest(id)
    replaceInToday(completed)
    return completed
  }

  /** Undo of a conclusion made by mistake. */
  async function undoCompletion(id: number): Promise<void> {
    replaceInToday(await reopenBookingRequest(id))
  }

  return { pendingRequests, todayAgenda, isLoading, hasFailed, load, accept, reject, complete, undoCompletion }
})
