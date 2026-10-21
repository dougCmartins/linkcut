<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'

/** linkcut's own page. Each barbershop lives at /:slug. */
const { t } = useI18n()
</script>

<template>
  <main class="mx-auto flex min-h-dvh w-full max-w-md flex-col items-center justify-center gap-4 px-5 text-center">
    <h1 class="text-3xl">{{ t('home.title') }}</h1>
    <p class="text-muted">{{ t('home.tagline') }}</p>
    <BaseButton class="mt-4" variant="primary" size="lg" elevated to="/cadastrar">{{ t('home.signUp') }}</BaseButton>
    <BaseButton variant="secondary" size="lg" to="/entrar">{{ t('home.login') }}</BaseButton>
  </main>
</template>
