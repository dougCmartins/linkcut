<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { usePageMeta } from '@/composables/usePageMeta'
import { useApiErrors } from '@/composables/useApiErrors'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { acceptInvitation } from './team.api'

/**
 * The link of the team invitation e-mail (/equipe/convite?token=…): the barber creates a password, accepts the terms
 * and enters the dashboard of the unit that invited them. The token is used once on the API.
 */
const { t } = useI18n()
const { messageKey } = useApiErrors()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
usePageMeta(() => t('invitation.metaTitle'), () => t('invitation.text'))

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const password = ref('')
const acceptTerms = ref(false)
const isSending = ref(false)
const errorMessage = ref('')

async function submit(): Promise<void> {
  if (isSending.value || !token.value) return
  isSending.value = true
  errorMessage.value = ''
  try {
    authStore.startSession(await acceptInvitation({ token: token.value, password: password.value, acceptTerms: acceptTerms.value }))
    await router.replace('/painel')
  } catch (caught) {
    errorMessage.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    isSending.value = false
  }
}
</script>

<template>
  <main class="mx-auto flex min-h-dvh w-full max-w-sm flex-col justify-center px-4 py-10">
    <h1 class="text-3xl leading-tight">{{ t('invitation.title') }}</h1>
    <p class="mt-2 text-sm text-muted">{{ t('invitation.text') }}</p>

    <p v-if="!token" class="mt-6 text-sm text-danger" role="alert">{{ t('invitation.missingToken') }}</p>
    <form v-else class="mt-6 grid gap-4" @submit.prevent="submit">
      <TextField v-model="password" :label="t('invitation.password')" type="password" autocomplete="new-password" :maxlength="72" />
      <label class="flex items-start gap-3 text-sm">
        <input v-model="acceptTerms" type="checkbox" class="mt-0.5 size-5 accent-[var(--color-accent)]" />
        <span>{{ t('invitation.acceptTerms') }}</span>
      </label>
      <p v-if="errorMessage" class="text-sm text-danger" role="alert">{{ errorMessage }}</p>
      <BaseButton type="submit" variant="primary" block :disabled="isSending || password.length < 8 || !acceptTerms">
        {{ isSending ? t('invitation.submitting') : t('invitation.submit') }}
      </BaseButton>
    </form>
  </main>
</template>
