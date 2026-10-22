<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { credentialsSchema } from '@/Domain/Identity/auth.schemas'
import { useApiErrors } from '@/composables/useApiErrors'
import AuthCardLayout from '@/components/ui/AuthCardLayout.vue'
import ClientPasswordForm from './ClientPasswordForm.vue'
import { safeClientRedirect } from './client.guard'
import { CodePurpose } from './client.model'
import { useClientStore } from './client.store'

/** /conta/entrar: e-mail and password, or "Esqueci minha senha" (new password with a code). */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const apiErrors = useApiErrors()
const clientStore = useClientStore()

const isResetting = ref(false)
const email = ref('')
const password = ref('')
const isBusy = ref(false)
const error = ref('')
const canSubmit = computed(
  () =>
    credentialsSchema.safeParse({
      email: email.value,
      password: password.value,
    }).success && !isBusy.value,
)

const goOn = () => router.replace(safeClientRedirect(route.query.redirect))

async function submit(): Promise<void> {
  if (!canSubmit.value) return
  isBusy.value = true
  error.value = ''
  try {
    await clientStore.login(email.value, password.value)
    await goOn()
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
  } finally {
    isBusy.value = false
  }
}
</script>

<template>
  <AuthCardLayout v-if="isResetting" :title="t('client.reset.title')" :subtitle="t('client.reset.subtitle')">
    <ClientPasswordForm :purpose="CodePurpose.Password" :initial-email="email" @done="goOn" />
    <template #footer>
      <button type="button" class="mt-6 text-center text-sm font-semibold text-muted hover:text-ink" @click="isResetting = false">
        {{ t('client.reset.back') }}
      </button>
    </template>
  </AuthCardLayout>

  <AuthCardLayout v-else :title="t('client.login.title')" :subtitle="t('client.login.subtitle')">
    <p v-if="route.query.conta === 'excluida'" class="mb-5 rounded-card bg-raised px-4 py-3 text-sm font-semibold ring-1 ring-line" role="status">
      {{ t('client.profile.deleted') }}
    </p>
    <p v-if="error" class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
      {{ error }}
    </p>
    <form class="grid gap-5" novalidate @submit.prevent="submit">
      <TextField v-model="email" :label="t('auth.email')" type="email" inputmode="email" autocomplete="email" />
      <TextField v-model="password" :label="t('auth.password')" type="password" autocomplete="current-password" />
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canSubmit">
        {{ isBusy ? t('auth.checking') : t('client.login.submit') }}
      </BaseButton>
      <button type="button" class="text-sm font-semibold text-accent" @click="isResetting = true">
        {{ t('client.login.forgot') }}
      </button>
    </form>
    <template #footer>
      <p class="mt-6 text-center text-sm text-muted">
        {{ t('client.login.noAccount') }}
      </p>
    </template>
  </AuthCardLayout>
</template>
