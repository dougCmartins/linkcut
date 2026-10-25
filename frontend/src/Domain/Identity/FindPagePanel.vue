<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AuthHeading from '@/components/ui/AuthHeading.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { isValidEmail } from '@/utils/email'
import { useApiErrors } from '@/composables/useApiErrors'
import { lastBarberSlug } from '@/Domain/Tenant/tenant.store'
import { sendLoginLink } from './auth.api'

/**
 * /entrar: barbers sign in on their own page (/<slug>/entrar). Here they get its link by e-mail, or go straight to
 * the page this device used last.
 */
const { t } = useI18n()
const route = useRoute()
const apiErrors = useApiErrors()

const lastSlug = lastBarberSlug()
const host = window.location.host
const email = ref('')
const isBusy = ref(false)
const sentTo = ref('')
const error = ref('')
const canSend = computed(() => isValidEmail(email.value.trim()) && !isBusy.value)

async function send(): Promise<void> {
  if (!canSend.value) return
  isBusy.value = true
  error.value = ''
  try {
    await sendLoginLink(email.value.trim())
    sentTo.value = email.value.trim()
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
  } finally {
    isBusy.value = false
  }
}
</script>

<template>
  <section>
    <p
      v-if="route.query.conta === 'excluida'"
      class="mb-6 rounded-card bg-raised px-4 py-3 text-sm font-semibold ring-1 ring-line"
      role="status"
    >
      {{ t('settings.account.deleted') }}
    </p>
    <AuthHeading :title="t('findPage.title')" :subtitle="t('findPage.subtitle')" />

    <BaseButton v-if="lastSlug" class="mb-6" variant="primary" size="lg" elevated block :to="`/${lastSlug}/entrar`">
      {{ t('findPage.continueOn', { address: `${host}/${lastSlug}` }) }}
    </BaseButton>

    <p v-if="sentTo" class="rounded-card bg-success/10 px-4 py-3 text-sm font-semibold text-success ring-1 ring-success/40" role="status">
      {{ t('findPage.sent', { email: sentTo }) }}
    </p>
    <form v-else class="grid gap-5" novalidate @submit.prevent="send">
      <p v-if="error" class="rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
        {{ error }}
      </p>
      <TextField v-model="email" :label="t('auth.email')" type="email" inputmode="email" autocomplete="email" />
      <BaseButton type="submit" :variant="lastSlug ? 'secondary' : 'primary'" size="lg" :elevated="!lastSlug" block :disabled="!canSend">
        {{ isBusy ? t('auth.checking') : t('findPage.send') }}
      </BaseButton>
    </form>

    <slot name="switch" />
  </section>
</template>
