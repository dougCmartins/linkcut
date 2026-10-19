<script setup lang="ts">
import { computed, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaletteToggle from '@/components/ui/PaletteToggle.vue'
import BookingModal from '@/Booking/BookingModal.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { provideButtonStyle } from '@/composables/useButtonStyle'
import { applyThemeToDocument, useVisitorMode } from '@/composables/useTheme'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const publicPageStore = usePublicPageStore()
const { visitorMode } = useVisitorMode()

// Public pages use the theme of the barbershop being visited (visitors may switch light/dark);
// the dashboard uses the barber's own saved theme.
const pageTheme = computed(() => (route.meta.isPublic ? publicPageStore.theme : settingsStore.theme))
watchEffect(() => applyThemeToDocument(pageTheme.value, route.meta.isPublic ? visitorMode.value : null))
provideButtonStyle(computed(() => pageTheme.value.buttonStyle))

// The dashboard pages need the barber's settings (name, photo, theme, services).
watch(
  () => route.meta.requiresAuth,
  (requiresAuth) => {
    if (requiresAuth) void settingsStore.ensureLoaded()
  },
  { immediate: true },
)
watch(
  () => authStore.user,
  (user) => {
    if (!user) settingsStore.clear()
  },
)

watch(
  () => route.meta.openBooking,
  (openBooking) => {
    if (openBooking) bookingStore.openModal()
  },
  { immediate: true },
)

function closeBooking(): void {
  bookingStore.closeModal()
  // Leave the /:slug/agendar deep link so the modal does not reopen.
  if (route.meta.openBooking) router.replace(`/${route.params.slug}`)
}
</script>

<template>
  <PaletteToggle v-if="route.meta.isPublic" class="fixed top-4 right-4 z-40" />
  <RouterView />
  <BookingModal v-if="bookingStore.isModalOpen && publicPageStore.bookingOptions" @close="closeBooking" />
</template>
