<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import OtpInput from '@/components/ui/OtpInput.vue'
import TextField from '@/components/ui/TextField.vue'
import { CODE_LENGTH, PASSWORD_MIN_LENGTH, codeSchema, credentialsSchema } from '@/Domain/Identity/auth.schemas'
import { useApiErrors } from '@/composables/useApiErrors'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { sendCode } from './client.api'
import type { CodePurpose } from './client.model'
import { useClientStore } from './client.store'

/**
 * Sign-up or new password, in two steps: the e-mail gets a code, then code + password open the session.
 * The code proves the e-mail is the client's (nobody takes an account with someone else's e-mail).
 */
const props = defineProps<{ purpose: CodePurpose; initialEmail?: string }>()
const emit = defineEmits<{ done: [] }>()
const { t } = useI18n()
const apiErrors = useApiErrors()
const clientStore = useClientStore()

const email = ref(props.initialEmail ?? '')
// The e-mail of the link (?email=) fills the field, unless the client already typed one.
watch(
  () => props.initialEmail,
  (value) => {
    if (value && !email.value) email.value = value
  },
)
const code = ref('')
const password = ref('')
const step = ref<'email' | 'code'>('email')
const isBusy = ref(false)
const error = ref('')
const resendAvailableAt = ref(0)
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined

const isEmailValid = computed(() => credentialsSchema.shape.email.safeParse(email.value).success)
const canSubmit = computed(() => codeSchema.safeParse(code.value).success && password.value.length >= PASSWORD_MIN_LENGTH && !isBusy.value)
const resendInSeconds = computed(() => Math.max(0, Math.ceil((resendAvailableAt.value - now.value) / 1000)))

async function run(action: () => Promise<void>): Promise<void> {
  isBusy.value = true
  error.value = ''
  try {
    await action()
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`, {
      attempts: apiErrors.attemptsLeft(caught) ?? 0,
    })
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.InvalidCode)) code.value = ''
    // An expired code or too many attempts: ask for a new one.
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.CodeExpired) || apiErrors.hasErrorCode(caught, ApiErrorCode.TooManyAttempts)) {
      code.value = ''
      step.value = 'email'
    }
  } finally {
    isBusy.value = false
  }
}

function requestCode(): Promise<void> {
  return run(async () => {
    const sent = await sendCode(email.value.trim(), props.purpose)
    resendAvailableAt.value = sent.resendAvailableAt
    step.value = 'code'
  })
}

function submit(): Promise<void> {
  if (!canSubmit.value) return Promise.resolve()
  return run(async () => {
    await clientStore.setPassword(props.purpose, {
      email: email.value.trim(),
      code: code.value,
      password: password.value,
    })
    emit('done')
  })
}

onMounted(() => (timer = setInterval(() => (now.value = Date.now()), 1000)))
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div>
    <p v-if="error" class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
      {{ error }}
    </p>

    <form v-if="step === 'email'" class="grid gap-5" novalidate @submit.prevent="requestCode">
      <TextField v-model="email" :label="t('auth.email')" type="email" inputmode="email" autocomplete="email" />
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!isEmailValid || isBusy">
        {{ t('client.sendCode') }}
      </BaseButton>
    </form>

    <form v-else class="grid gap-5" novalidate @submit.prevent="submit">
      <p class="text-sm leading-relaxed text-muted" role="status">
        {{ t('client.codeSent', { email }) }}
      </p>
      <OtpInput v-model="code" :length="CODE_LENGTH" :label="t('auth.codeLabel')" :disabled="isBusy" :invalid="Boolean(error)" />
      <TextField
        v-model="password"
        :label="t('client.newPassword')"
        type="password"
        autocomplete="new-password"
        :hint="t('client.passwordHint', { min: PASSWORD_MIN_LENGTH })"
      />
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canSubmit">
        {{ isBusy ? t('auth.checking') : t(`client.submit.${purpose}`) }}
      </BaseButton>
      <div class="flex flex-wrap items-center justify-between gap-3 text-sm font-semibold">
        <button type="button" class="text-accent disabled:text-muted" :disabled="resendInSeconds > 0 || isBusy" @click="requestCode">
          {{ resendInSeconds > 0 ? t('auth.resendIn', { seconds: resendInSeconds }) : t('auth.resend') }}
        </button>
        <button type="button" class="text-muted hover:text-ink" @click="step = 'email'">
          {{ t('client.changeEmail') }}
        </button>
      </div>
      <p class="text-xs text-muted">{{ t('client.codeHelp') }}</p>
    </form>
  </div>
</template>
