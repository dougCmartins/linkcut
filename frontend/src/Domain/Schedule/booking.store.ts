import { defineStore } from 'pinia'
import { ref } from 'vue'

/** The public booking modal (opened from the bio link, the portfolio or /:slug/agendar). */
export const useBookingStore = defineStore('booking', () => {
  const isModalOpen = ref(false)

  const openModal = () => (isModalOpen.value = true)
  const closeModal = () => (isModalOpen.value = false)

  return { isModalOpen, openModal, closeModal }
})
