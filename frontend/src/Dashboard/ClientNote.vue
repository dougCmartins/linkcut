<script setup lang="ts">
import { useI18n } from 'vue-i18n'

/** The client's note to the barber, from the booking ("cabelo cacheado"). */
defineProps<{ note: string | null }>()
const { t } = useI18n()
</script>

<template>
  <p v-if="note" class="mt-3 rounded-2xl bg-raised px-3 py-2 text-sm break-words">
    <span class="font-semibold">{{ t('dashboard.clientNote') }}</span> {{ note }}
  </p>
</template>
