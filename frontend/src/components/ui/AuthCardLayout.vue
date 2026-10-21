<script setup lang="ts">
import BaseCard from '@/components/ui/BaseCard.vue'

/** Frame of the sign-in and sign-up pages (barber and client): logo, one card. */
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <main class="mx-auto flex min-h-dvh w-full max-w-md flex-col justify-center px-5 py-10">
    <p class="mx-auto mb-8 flex items-center gap-2 font-display text-xl font-extrabold tracking-tight">
      <span class="flex size-9 items-center justify-center rounded-xl bg-accent text-on-accent shadow-soft" aria-hidden="true">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="6" cy="6" r="3" />
          <circle cx="6" cy="18" r="3" />
          <path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12" />
        </svg>
      </span>
      LinkCut
    </p>
    <BaseCard floating class="p-6">
      <h1 class="text-2xl leading-tight">{{ title }}</h1>
      <p v-if="subtitle" class="mt-1.5 mb-6 text-sm text-muted">
        {{ subtitle }}
      </p>
      <slot />
    </BaseCard>
    <slot name="footer" />
  </main>
</template>
