<script setup lang="ts">
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaletteToggle from '@/components/ui/PaletteToggle.vue'
import BookingModal from '@/Booking/BookingModal.vue'
import { useBookingStore } from '@/Booking/booking.store'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()

watch(
  () => route.meta.openBooking,
  (openBooking) => {
    if (openBooking) bookingStore.openModal()
  },
  { immediate: true },
)

function closeBooking(): void {
  bookingStore.closeModal()
  // Leave the /agendar deep link so the modal does not reopen.
  if (route.meta.openBooking) router.replace('/')
}
</script>

<template>
  <PaletteToggle class="fixed top-4 right-4 z-40" />
  <RouterView />
  <BookingModal v-if="bookingStore.isModalOpen" @close="closeBooking" />
</template>
