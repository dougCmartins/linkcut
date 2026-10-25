<script setup lang="ts">
import { computed, defineAsyncComponent, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PaletteToggle from '@/components/ui/PaletteToggle.vue'
import { useConsentStore } from '@/Legal/consent.store'
import { useBookingStore } from '@/Domain/Schedule/booking.store'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { useTenantStore } from '@/Domain/Tenant/tenant.store'
import { provideButtonStyle } from '@/composables/useButtonStyle'
import { applyThemeToDocument, useVisitorMode } from '@/composables/useTheme'
import { leaveTo } from '@/composables/useQueryOverlay'

// Loaded only when "Agendar" opens: the rest of the public page does not wait for the booking code.
const BookingModal = defineAsyncComponent(() => import('@/Domain/Schedule/BookingModal.vue'))

// The privacy notice downloads only when it has to show (first visit, or "Preferências de cookies").
const CookieBanner = defineAsyncComponent(() => import('@/Legal/CookieBanner.vue'))
const consent = useConsentStore()

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const publicPageStore = usePublicPageStore()
const tenantStore = useTenantStore()
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
    if (!user) {
      settingsStore.clear()
      tenantStore.clear()
    }
  },
)

// The booking modal follows the address (/:slug/agendar): the phone's back button closes it, not the page.
watch(
  () => route.meta.openBooking,
  (openBooking) => (openBooking ? bookingStore.openModal() : bookingStore.closeModal()),
  { immediate: true },
)

// The booking modal belongs to the public pages: leaving them (e.g. "Finalizar cadastro") closes it.
watch(
  () => route.meta.isPublic,
  (isPublic) => {
    if (!isPublic) bookingStore.closeModal()
  },
)

function closeBooking(): void {
  bookingStore.closeModal()
  // Back to the page it was opened from, or (a shared /agendar link) to the page under it, without leaving the app.
  if (route.meta.openBooking) leaveTo(router, route.path.replace(/\/agendar\/?$/, ''))
}
</script>

<template>
  <PaletteToggle v-if="route.meta.isPublic" class="fixed top-4 right-4 z-40" />
  <RouterView />
  <BookingModal v-if="bookingStore.isModalOpen && publicPageStore.bookingOptions" @close="closeBooking" />
  <CookieBanner v-if="!consent.hasDecided || consent.isPanelOpen" />
</template>
