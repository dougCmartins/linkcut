<script setup lang="ts">
import { watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaletteToggle from '@/components/ui/PaletteToggle.vue'
import BookingModal from '@/Booking/BookingModal.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { useSettingsStore } from '@/Settings/settings.store'
import { applyThemeToDocument, useVisitorMode } from '@/composables/useTheme'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()
const settingsStore = useSettingsStore()
const { visitorMode } = useVisitorMode()

// The barber's saved theme everywhere; visitors may switch light/dark on public pages only.
watchEffect(() => applyThemeToDocument(settingsStore.theme, route.meta.isPublic ? visitorMode.value : null))

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
  <PaletteToggle v-if="route.meta.isPublic" class="fixed top-4 right-4 z-40" />
  <RouterView />
  <BookingModal v-if="bookingStore.isModalOpen" @close="closeBooking" />
</template>
