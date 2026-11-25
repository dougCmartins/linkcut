<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AuthHeading from '@/components/ui/AuthHeading.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import OtpInput from '@/components/ui/OtpInput.vue'
import TextField from '@/components/ui/TextField.vue'
import { CODE_LENGTH, PASSWORD_MIN_LENGTH, codeSchema, credentialsSchema } from '@/Domain/Identity/auth.schemas'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useApiErrors } from '@/composables/useApiErrors'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { sendSignUpCode, signUp } from './onboarding.api'
import SlugField from './SlugField.vue'
import { slugify } from './slug'
import { useSlugCheck } from './useSlugCheck'
import LegalDialog from '@/Legal/LegalDialog.vue'
import { useLegalOverlay } from '@/Legal/useLegalDocument'

/**
 * Open sign-up (on /, /cadastrar), in three steps. The account (name, e-mail, password) → the code sent to the e-mail →
 * the barbershop name and its link (linkcut.app/<slug>, checked while typing). The API checks the code at the end
 * and answers with the session, so the barber goes straight to the setup wizard.
 */
type Step = 'account' | 'code' | 'barbershop'
const NAME_MIN_LENGTH = 2

const { t } = useI18n()
/** Terms and policy open over the form, so nothing typed is lost. */
const legal = useLegalOverlay()
const router = useRouter()
const auth = useAuthStore()
const apiErrors = useApiErrors()

const step = ref<Step>('account')
const form = reactive({ name: '', email: '', password: '', code: '', barbershopName: '', slug: '', acceptTerms: false })
const isBusy = ref(false)
const error = ref('')
const resendAvailableAt = ref(0)
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined

const isAccountValid = computed(
  () =>
    form.name.trim().length >= NAME_MIN_LENGTH &&
    credentialsSchema.safeParse({ email: form.email, password: form.password }).success &&
    form.password.length >= PASSWORD_MIN_LENGTH,
)
const isCodeComplete = computed(() => codeSchema.safeParse(form.code).success)
const resendInSeconds = computed(() => Math.max(0, Math.ceil((resendAvailableAt.value - now.value) / 1000)))

// The link follows the barbershop name until the barber edits it.
const slugEdited = ref(false)
const { status: slugStatus, suggestion: slugSuggestion, recheck: recheckSlug } = useSlugCheck(toRef(form, 'slug'))

watch(
  () => form.barbershopName,
  (name) => {
    if (!slugEdited.value) form.slug = slugify(name)
  },
)

const canFinish = computed(
  () => form.barbershopName.trim().length >= NAME_MIN_LENGTH && slugStatus.value === 'available' && form.acceptTerms && !isBusy.value,
)

async function run(action: () => Promise<void>): Promise<void> {
  isBusy.value = true
  error.value = ''
  try {
    await action()
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`, { attempts: apiErrors.attemptsLeft(caught) ?? 0 })
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.InvalidCode)) {
      form.code = ''
      step.value = 'code'
    }
    // An expired code or too many attempts: back to the start, to ask for a new code.
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.CodeExpired) || apiErrors.hasErrorCode(caught, ApiErrorCode.TooManyAttempts)) {
      form.code = ''
      step.value = 'account'
    }
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.SlugUnavailable)) recheckSlug()
  } finally {
    isBusy.value = false
  }
}

function requestCode(): Promise<void> {
  return run(async () => {
    resendAvailableAt.value = await sendSignUpCode(form.email.trim())
    form.code = ''
    step.value = 'code'
  })
}

function confirmCode(): void {
  if (!isCodeComplete.value) return
  error.value = ''
  step.value = 'barbershop'
}

function finish(): Promise<void> {
  if (!canFinish.value) return Promise.resolve()
  return run(async () => {
    const session = await signUp({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      code: form.code,
      barbershopName: form.barbershopName.trim(),
      slug: form.slug,
      acceptTerms: form.acceptTerms,
    })
    auth.startSession(session)
    await router.replace({ name: 'onboarding' })
  })
}

onMounted(() => (timer = setInterval(() => (now.value = Date.now()), 1000)))
onBeforeUnmount(() => {
  clearInterval(timer)
})
</script>

<template>
  <section>
    <AuthHeading :title="t(`signUp.steps.${step}.title`)" :subtitle="t(`signUp.steps.${step}.subtitle`)" />
    <p v-if="error" class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
      {{ error }}
    </p>

    <form v-if="step === 'account'" class="grid grid-cols-1 gap-5" novalidate @submit.prevent="requestCode">
      <TextField v-model="form.name" :label="t('signUp.name')" autocomplete="name" :maxlength="60" />
      <TextField v-model="form.email" :label="t('auth.email')" type="email" inputmode="email" autocomplete="email" />
      <TextField
        v-model="form.password"
        :label="t('auth.password')"
        type="password"
        autocomplete="new-password"
        :hint="t('client.passwordHint', { min: PASSWORD_MIN_LENGTH })"
      />
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!isAccountValid || isBusy">
        {{ t('signUp.sendCode') }}
      </BaseButton>
    </form>

    <form v-else-if="step === 'code'" class="grid grid-cols-1 gap-5" novalidate @submit.prevent="confirmCode">
      <p class="text-sm leading-relaxed text-muted" role="status">{{ t('signUp.codeSent', { email: form.email.trim() }) }}</p>
      <OtpInput v-model="form.code" :length="CODE_LENGTH" :label="t('auth.codeLabel')" :disabled="isBusy" @complete="confirmCode" />
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!isCodeComplete">
        {{ t('auth.continue') }}
      </BaseButton>
      <div class="flex flex-wrap items-center justify-between gap-3 text-sm font-semibold">
        <button type="button" class="text-accent disabled:text-muted" :disabled="resendInSeconds > 0 || isBusy" @click="requestCode">
          {{ resendInSeconds > 0 ? t('auth.resendIn', { seconds: resendInSeconds }) : t('auth.resend') }}
        </button>
        <button type="button" class="text-muted hover:text-ink" @click="step = 'account'">{{ t('client.changeEmail') }}</button>
      </div>
      <p class="text-xs text-muted">{{ t('signUp.codeHelp') }}</p>
    </form>

    <form v-else class="grid grid-cols-1 gap-5" novalidate @submit.prevent="finish">
      <TextField v-model="form.barbershopName" :label="t('signUp.barbershopName')" :maxlength="40" :hint="t('signUp.barbershopHint')" />
      <SlugField
        id="sign-up-slug"
        v-model="form.slug"
        :label="t('signUp.slug.label')"
        :status="slugStatus"
        :suggestion="slugSuggestion"
        @edit="slugEdited = true"
      />
      <label class="flex items-start gap-3 text-sm leading-relaxed">
        <input v-model="form.acceptTerms" type="checkbox" class="mt-1 size-4 shrink-0 accent-[var(--accent)]" />
        <span>
          {{ t('signUp.terms.accept') }}
          <a href="/termos" class="font-semibold underline" @click.prevent="legal.open('terms')">{{ t('privacy.links.terms') }}</a>
          {{ t('signUp.terms.and') }}
          <a href="/privacidade" class="font-semibold underline" @click.prevent="legal.open('privacy')">{{ t('privacy.links.policy') }}</a
          >.
        </span>
      </label>
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canFinish">
        {{ isBusy ? t('auth.checking') : t('signUp.finish') }}
      </BaseButton>
      <button type="button" class="text-sm font-semibold text-muted hover:text-ink" @click="step = 'code'">{{ t('signUp.back') }}</button>
    </form>

    <!-- "Já tem conta? Entrar", only before the e-mail code is asked. -->
    <slot v-if="step === 'account'" name="switch" />
    <LegalDialog v-if="legal.document.value" :document="legal.document.value" @close="legal.close()" />
  </section>
</template>
