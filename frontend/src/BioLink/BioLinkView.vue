<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SmartImage from '@/components/ui/SmartImage.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'
import { useBookingStore } from '@/Booking/booking.store'
import { barberProfile } from '@/mocks/barber'

const { t } = useI18n()
const bookingStore = useBookingStore()
const profile = barberProfile

const whatsappUrl = computed(
  () => `https://wa.me/${profile.whatsappNumber}?text=${encodeURIComponent(t('bioLink.whatsappMessage'))}`,
)
</script>

<template>
  <main class="relative min-h-dvh overflow-hidden">
    <!-- Background photo with an overlay so text stays readable -->
    <div class="absolute inset-0 -z-10" aria-hidden="true">
      <SmartImage :src="profile.coverUrl" alt="" class="size-full object-cover opacity-60 blur-[2px] scale-105" />
      <div class="absolute inset-0 [background:var(--hero-overlay)]" />
    </div>

    <div class="mx-auto flex min-h-dvh w-full max-w-md flex-col items-center px-5 pt-20 pb-10">
      <header class="flex flex-col items-center text-center">
        <AvatarImage :src="profile.avatarUrl" :alt="t('bioLink.avatarAlt', { name: profile.name })" />
        <h1 class="mt-5 text-3xl leading-none">{{ profile.name }}</h1>
        <p class="mt-1.5 text-sm font-semibold text-accent">{{ profile.handle }}</p>
        <p class="mt-3 max-w-xs text-[15px] leading-relaxed text-muted">{{ profile.bio }}</p>
      </header>

      <nav class="mt-10 flex w-full flex-col gap-5">
        <BaseButton variant="primary" size="lg" elevated block @click="bookingStore.openModal()">
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M16 3v4M8 3v4M3 10h18" />
          </svg>
          {{ t('bioLink.book') }}
        </BaseButton>
        <BaseButton variant="secondary" size="lg" elevated block to="/portfolio">
          {{ t('bioLink.portfolio') }}
        </BaseButton>
        <BaseButton variant="secondary" size="lg" elevated block :href="whatsappUrl">
          <svg class="size-5 text-success" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 2 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2Z" />
          </svg>
          {{ t('bioLink.whatsapp') }}
        </BaseButton>
      </nav>

      <footer class="mt-auto flex flex-col items-center gap-6 pt-12">
        <SocialLinks :links="profile.socials" :label="t('bioLink.socials')" />
        <RouterLink to="/painel" class="text-xs font-semibold tracking-wide text-muted transition hover:text-ink">
          {{ t('bioLink.poweredBy') }}
        </RouterLink>
      </footer>
    </div>
  </main>
</template>
