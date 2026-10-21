<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AuthCardLayout from '@/components/ui/AuthCardLayout.vue'
import ClientPasswordForm from './ClientPasswordForm.vue'
import { CLIENT_HOME } from './client.guard'
import { CodePurpose } from './client.model'

/** /conta/finalizar?email=…: opened from the booking success card or the e-mails. */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const email = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''))
</script>

<template>
  <AuthCardLayout :title="t('client.register.title')" :subtitle="t('client.register.subtitle')">
    <ClientPasswordForm :purpose="CodePurpose.Registration" :initial-email="email" @done="router.replace(CLIENT_HOME)" />
    <template #footer>
      <RouterLink :to="{ name: 'client-login' }" class="mt-6 text-center text-sm font-semibold text-muted hover:text-ink">
        {{ t('client.register.haveAccount') }}
      </RouterLink>
    </template>
  </AuthCardLayout>
</template>
