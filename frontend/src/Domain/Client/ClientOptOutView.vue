<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AuthCardLayout from '@/components/ui/AuthCardLayout.vue'

/** Where the signed opt-out link of the e-mails lands (the API already turned the reminders off). */
const { t } = useI18n()
</script>

<template>
  <AuthCardLayout :title="t('client.optOut.title')">
    <p class="mt-3 text-sm leading-relaxed text-muted" role="status">
      {{ t('client.optOut.text') }}
    </p>
  </AuthCardLayout>
</template>
