<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import { LoadStatus } from '@/types/loadStatus'

/** What a visitor sees while the barbershop loads, or when it does not exist. */
defineProps<{ status: LoadStatus }>()
const emit = defineEmits<{ retry: [] }>()
const { t } = useI18n()
</script>

<template>
  <main class="mx-auto flex min-h-dvh w-full max-w-md flex-col items-center justify-center gap-4 px-5 text-center">
    <template v-if="status === LoadStatus.NotFound">
      <h1 class="text-2xl">{{ t('publicPage.notFound') }}</h1>
      <p class="text-sm text-muted">{{ t('publicPage.notFoundHint') }}</p>
      <BaseButton variant="secondary" to="/">{{ t('publicPage.home') }}</BaseButton>
    </template>
    <template v-else-if="status === LoadStatus.Failed">
      <p class="text-sm text-muted" role="alert">{{ t('publicPage.failed') }}</p>
      <BaseButton variant="secondary" @click="emit('retry')">{{ t('publicPage.retry') }}</BaseButton>
    </template>
    <p v-else class="text-sm text-muted" role="status">{{ t('publicPage.loading') }}</p>
  </main>
</template>
