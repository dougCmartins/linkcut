<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import ClientPushOffer from '@/Domain/Client/ClientPushOffer.vue'

/**
 * `registerEmail`: shows the "Finalizar cadastro" card (the client is not signed in to the client area).
 * `pushUrl`: offers "Avisar neste aparelho" for the barbershop's answer.
 */
defineProps<{ summary: string; registerEmail?: string | null; pushUrl?: string | null }>()
const emit = defineEmits<{ done: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="flex flex-col items-center px-2 py-6 text-center" role="status">
    <span class="flex size-20 items-center justify-center rounded-full bg-accent text-on-accent shadow-float" aria-hidden="true">
      <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
        <path d="m5 12.5 4.5 4.5L19 7.5" />
      </svg>
    </span>
    <h3 class="mt-6 text-2xl">{{ t('booking.successTitle') }}</h3>
    <p class="mt-2 text-muted">{{ t('booking.successText') }}</p>
    <p class="mt-5 rounded-full bg-raised px-4 py-2 text-sm font-semibold ring-1 ring-line">{{ summary }}</p>
    <ClientPushOffer v-if="pushUrl" class="mt-6" :push-url="pushUrl" />
    <RouterLink
      v-if="registerEmail"
      :to="{ name: 'client-register', query: { email: registerEmail } }"
      class="mt-6 block w-full rounded-card bg-accent/10 p-4 text-left ring-1 ring-accent/40"
    >
      <span class="block font-bold">{{ t('booking.registerTitle') }}</span>
      <span class="mt-1 block text-sm text-muted">{{ t('booking.registerText') }}</span>
      <span class="mt-2 block text-sm font-bold text-accent">{{ t('booking.registerAction') }} →</span>
    </RouterLink>
    <BaseButton class="mt-6" variant="secondary" elevated block @click="emit('done')">
      {{ t('booking.successDone') }}
    </BaseButton>
  </div>
</template>
