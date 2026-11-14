import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { BookingRequest } from './booking.model'
import {
  acceptBookingRequest,
  completeBookingRequest,
  fetchConfirmedAppointments,
  fetchPendingRequests,
  fetchTodayAgenda,
  rejectBookingRequest,
  reopenBookingRequest,
} from './booking.api'

/** How many next appointments the dashboard shows. */
export const UPCOMING_LIMIT = 20

/** The barber's side of the agenda (dashboard): pending requests, today's and the next appointments, from the API. */
export const useAgendaStore = defineStore('agenda', () => {
  const pendingRequests = ref<BookingRequest[]>([])
  const todayAgenda = ref<BookingRequest[]>([])
  /** Confirmed appointments of the next days (today's are in the agenda above). */
  const upcoming = ref<BookingRequest[]>([])
  const isLoading = ref(false)
  /** False until the first answer: the dashboard shows placeholders instead of "nothing here". */
  const isLoaded = ref(false)

  async function fetchAppointments(): Promise<[BookingRequest[], BookingRequest[]]> {
    const [today, confirmed] = await Promise.all([fetchTodayAgenda(), fetchConfirmedAppointments()])
    const todayIds = new Set(today.map((item) => item.id))
    return [today, confirmed.filter((item) => !todayIds.has(item.id)).slice(0, UPCOMING_LIMIT)]
  }
  const hasFailed = ref(false)

  /** Only the newest answer is kept: a slow background refresh never overwrites a newer list. */
  let latestLoad = 0

  /**
   * Loads the three lists. `silent` (background refresh): no loading state, and on failure the lists already on
   * screen stay as they are, without the error message.
   */
  async function load(options: { silent?: boolean } = {}): Promise<void> {
    const request = ++latestLoad
    if (!options.silent) {
      isLoading.value = true
      hasFailed.value = false
    }
    try {
      const [pending, [today, next]] = await Promise.all([fetchPendingRequests(), fetchAppointments()])
      if (request !== latestLoad) return
      pendingRequests.value = pending
      todayAgenda.value = today
      upcoming.value = next
      hasFailed.value = false
    } catch {
      if (request === latestLoad && !options.silent) hasFailed.value = true
    } finally {
      if (!options.silent) isLoading.value = false
      isLoaded.value = true
    }
  }

  /** Replaces an appointment of today's agenda with its saved version. */
  function replaceInToday(request: BookingRequest): void {
    todayAgenda.value = todayAgenda.value.map((item) => (item.id === request.id ? request : item))
  }

  async function accept(id: number): Promise<void> {
    await acceptBookingRequest(id)
    pendingRequests.value = pendingRequests.value.filter((item) => item.id !== id)
    // Reloaded rather than guessed: only the API knows the barbershop's "today", so it lands in the right list.
    ;[todayAgenda.value, upcoming.value] = await fetchAppointments()
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

  return { pendingRequests, todayAgenda, upcoming, isLoading, isLoaded, hasFailed, load, accept, reject, complete, undoCompletion }
})
