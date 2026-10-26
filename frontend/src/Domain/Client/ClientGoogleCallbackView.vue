<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AuthHeading from '@/components/ui/AuthHeading.vue'
import AuthSplitLayout from '@/components/ui/AuthSplitLayout.vue'
import { AUTH_PHOTOS } from '@/assets/auth/authPhotos'
import { useApiErrors } from '@/composables/useApiErrors'
import { usePageMeta } from '@/composables/usePageMeta'
import { clientLoginLocation, safeClientRedirect } from './client.guard'
import { useClientStore } from './client.store'
import { takeGoogleNonce } from './googleNonce'

/** Google sent the browser back with a one-time code. This page trades it for the session and leaves. */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const apiErrors = useApiErrors()
const clientStore = useClientStore()
const error = ref('')
const started = ref(false)

const photo = { ...AUTH_PHOTOS.login, alt: t('authPage.photos.login') }

async function signIn(): Promise<void> {
  const code = typeof route.query.codigo === 'string' ? route.query.codigo : ''
  const volta = safeClientRedirect(route.query.volta)
  if (!code) {
    error.value = t('client.google.failed')
    return
  }
  try {
    await clientStore.exchangeGoogle(code, takeGoogleNonce())
    await router.replace(volta)
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
  }
}

watch(
  () => route.fullPath,
  () => {
    if (route.name !== 'client-google' || started.value) return
    started.value = true
    void signIn()
  },
  { immediate: true },
)

usePageMeta(
  () => t('client.google.signingIn'),
  () => t('authPage.client.login.subtitle'),
)
</script>

<template>
  <AuthSplitLayout :photo="photo" photo-key="google">
    <AuthHeading :title="t('client.google.signingIn')" :subtitle="t('authPage.client.login.subtitle')" />
    <p v-if="error" class="rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
      {{ error }}
    </p>
    <p v-else class="text-sm font-semibold text-muted" role="status">{{ t('client.google.signingIn') }}</p>
    <p v-if="error" class="mt-8 text-center text-sm">
      <RouterLink :to="clientLoginLocation()" class="font-semibold text-accent underline underline-offset-4">
        {{ t('client.google.back') }}
      </RouterLink>
    </p>
  </AuthSplitLayout>
</template>
