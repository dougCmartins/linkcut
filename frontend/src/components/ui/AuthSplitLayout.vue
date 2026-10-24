<script setup lang="ts">
import PrivacyLinks from '@/Legal/PrivacyLinks.vue'

export interface AuthPhoto {
  /** Small file, also used as the fallback src. */
  src: string
  srcset: string
  alt: string
}

/**
 * Sign-up and sign-in pages (barber and client), in the "link in bio" style: the form on the left and a photo on
 * the right that changes with the mode. Only the content changes when the mode changes; the page stays.
 * Phones show only the form and never download the photo (the <source> only matches wide screens).
 */
defineProps<{ photo: AuthPhoto; photoKey: string }>()

/** 1×1 transparent GIF: what narrow screens "load" instead of the photo. */
const EMPTY_IMAGE = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
</script>

<template>
  <div class="min-h-dvh lg:grid lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    <div class="flex min-h-dvh flex-col px-5 pt-6 pb-6 sm:px-10 lg:px-12 lg:pt-12">
      <RouterLink to="/" class="flex w-fit items-center gap-2 font-display text-xl font-extrabold tracking-tight">
        <span class="flex size-9 items-center justify-center rounded-xl bg-accent text-on-accent shadow-soft" aria-hidden="true">
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="6" cy="6" r="3" />
            <circle cx="6" cy="18" r="3" />
            <path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12" />
          </svg>
        </span>
        LinkCut
      </RouterLink>

      <main class="mx-auto flex w-full max-w-[37rem] flex-1 flex-col justify-center py-10 lg:py-16">
        <Transition name="auth-form" mode="out-in">
          <div :key="photoKey">
            <slot />
          </div>
        </Transition>
      </main>

      <footer class="mx-auto w-full max-w-[37rem]">
        <PrivacyLinks />
      </footer>
    </div>

    <aside class="relative hidden overflow-hidden bg-raised lg:block">
      <Transition name="auth-photo">
        <picture :key="photoKey" class="absolute inset-0">
          <source media="(min-width: 1024px)" :srcset="photo.srcset" sizes="40vw" />
          <img :src="EMPTY_IMAGE" :alt="photo.alt" class="size-full object-cover" decoding="async" fetchpriority="high" />
        </picture>
      </Transition>
    </aside>
  </div>
</template>

<style scoped>
.auth-form-enter-active,
.auth-form-leave-active {
  transition:
    opacity 180ms ease,
    transform 180ms ease;
}
.auth-form-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.auth-form-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
.auth-photo-enter-active,
.auth-photo-leave-active {
  transition: opacity 400ms ease;
}
.auth-photo-enter-from,
.auth-photo-leave-to {
  opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
  .auth-form-enter-active,
  .auth-form-leave-active,
  .auth-photo-enter-active,
  .auth-photo-leave-active {
    transition: none;
  }
}
</style>
