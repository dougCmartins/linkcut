<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SmartImage from '@/components/ui/SmartImage.vue'
import AboutCard from './AboutCard.vue'
import WorkGallery from './WorkGallery.vue'
import StickyBookingBar from './StickyBookingBar.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { useSettingsStore } from '@/Settings/settings.store'

const { t } = useI18n()
const bookingStore = useBookingStore()
const settingsStore = useSettingsStore()

const name = computed(() => settingsStore.profile.name)
const portfolio = computed(() => settingsStore.portfolio)
const coverUrl = computed(() => settingsStore.resolveImage(portfolio.value.cover))
const photos = computed(() =>
  portfolio.value.photos.map((photo) => ({ id: photo.id, url: settingsStore.resolveImage(photo.src), alt: photo.alt })),
)
</script>

<template>
  <main class="pb-32">
    <div class="relative h-80 overflow-hidden">
      <SmartImage :src="coverUrl" :alt="t('portfolio.coverAlt', { name })" class="size-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-page" aria-hidden="true" />
      <RouterLink
        to="/"
        :aria-label="t('portfolio.back')"
        class="absolute top-4 left-4 flex size-10 items-center justify-center rounded-full bg-surface/80 text-ink ring-1 ring-line shadow-soft backdrop-blur"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </RouterLink>
    </div>

    <div class="mx-auto -mt-24 flex w-full max-w-md flex-col gap-10 px-4">
      <AboutCard :name="name" :portfolio="portfolio" />
      <WorkGallery
        v-if="photos.length"
        :photos="photos"
        :title="t('portfolio.galleryTitle')"
        :subtitle="t('portfolio.gallerySubtitle')"
      />
    </div>

    <StickyBookingBar @book="bookingStore.openModal()" />
  </main>
</template>
