import { defineStore } from 'pinia'
import { ref } from 'vue'

/** A booking that finished after the client closed the modal: App.vue shows it at the bottom of the page. */
export interface BookingNotice {
  kind: 'sent' | 'failed'
  message: string
}

/** The public booking modal (opened from the bio link, the portfolio or /:slug/agendar). */
export const useBookingStore = defineStore('booking', () => {
  const isModalOpen = ref(false)
  const notice = ref<BookingNotice | null>(null)

  const openModal = () => (isModalOpen.value = true)
  const closeModal = () => (isModalOpen.value = false)
  const showNotice = (value: BookingNotice | null) => (notice.value = value)

  return { isModalOpen, notice, openModal, closeModal, showNotice }
})
