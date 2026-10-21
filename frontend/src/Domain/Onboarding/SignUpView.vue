<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AuthCardLayout from '@/components/ui/AuthCardLayout.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import OtpInput from '@/components/ui/OtpInput.vue'
import TextField from '@/components/ui/TextField.vue'
import { CODE_LENGTH, PASSWORD_MIN_LENGTH, codeSchema, credentialsSchema } from '@/Domain/Identity/auth.schemas'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useApiErrors } from '@/composables/useApiErrors'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { checkSlug, sendSignUpCode, signUp } from './onboarding.api'
import { SLUG_MAX_LENGTH, isReservedSlug, isValidSlug, slugify } from './slug'

/**
 * /cadastrar: open sign-up in three steps. The account (name, e-mail, password) → the code sent to the e-mail →
 * the barbershop name and its link (linkcut.app/<slug>, checked while typing). The API checks the code at the end
 * and answers with the session, so the barber goes straight to the setup wizard.
 */
type Step = 'account' | 'code' | 'barbershop'
type SlugStatus = 'idle' | 'checking' | 'available' | 'taken' | 'invalid'

const SLUG_CHECK_DELAY_MS = 400
/** Where the public page will live, e.g. "linkcut.app/". */
const publicHost = `${window.location.host}/`
const NAME_MIN_LENGTH = 2

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const apiErrors = useApiErrors()

const step = ref<Step>('account')
const form = reactive({ name: '', email: '', password: '', code: '', barbershopName: '', slug: '' })
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
const slugStatus = ref<SlugStatus>('idle')
const slugSuggestion = ref('')
let slugTimer: ReturnType<typeof setTimeout> | undefined
let slugRequest = 0

watch(
  () => form.barbershopName,
  (name) => {
    if (!slugEdited.value) form.slug = slugify(name)
  },
)

watch(
  () => form.slug,
  (slug) => {
    clearTimeout(slugTimer)
    slugSuggestion.value = ''
    if (!slug) return void (slugStatus.value = 'idle')
    if (!isValidSlug(slug)) return void (slugStatus.value = 'invalid')
    if (isReservedSlug(slug)) {
      slugStatus.value = 'taken'
      slugSuggestion.value = `${slug}-barbearia`.slice(0, SLUG_MAX_LENGTH)
      return
    }
    slugStatus.value = 'checking'
    slugTimer = setTimeout(() => void verifySlug(slug), SLUG_CHECK_DELAY_MS)
  },
)

async function verifySlug(slug: string): Promise<void> {
  const request = ++slugRequest
  try {
    const answer = await checkSlug(slug)
    if (request !== slugRequest || slug !== form.slug) return
    slugStatus.value = answer.available ? 'available' : 'taken'
    slugSuggestion.value = answer.available ? '' : answer.suggestion
  } catch {
    if (request === slugRequest) slugStatus.value = 'idle'
  }
}

function editSlug(value: string): void {
  slugEdited.value = true
  form.slug = slugify(value)
}

const slugMessage = computed(() => (slugStatus.value === 'idle' ? '' : t(`signUp.slug.${slugStatus.value}`)))
const canFinish = computed(() => form.barbershopName.trim().length >= NAME_MIN_LENGTH && slugStatus.value === 'available' && !isBusy.value)

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
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.SlugUnavailable)) void verifySlug(form.slug)
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
    })
    auth.startSession(session)
    await router.replace({ name: 'onboarding' })
  })
}

onMounted(() => (timer = setInterval(() => (now.value = Date.now()), 1000)))
onBeforeUnmount(() => {
  clearInterval(timer)
  clearTimeout(slugTimer)
})
</script>

<template>
  <AuthCardLayout :title="t(`signUp.steps.${step}.title`)" :subtitle="t(`signUp.steps.${step}.subtitle`)">
    <p v-if="error" class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
      {{ error }}
    </p>

    <form v-if="step === 'account'" class="grid gap-5" novalidate @submit.prevent="requestCode">
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

    <form v-else-if="step === 'code'" class="grid gap-5" novalidate @submit.prevent="confirmCode">
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

    <form v-else class="grid gap-5" novalidate @submit.prevent="finish">
      <TextField v-model="form.barbershopName" :label="t('signUp.barbershopName')" :maxlength="40" :hint="t('signUp.barbershopHint')" />
      <div>
        <label for="sign-up-slug" class="block pl-2 text-xs font-semibold text-muted">{{ t('signUp.slug.label') }}</label>
        <div
          class="mt-1.5 flex h-12 items-center rounded-full bg-surface pl-5 ring-1 ring-line shadow-soft focus-within:ring-2 focus-within:ring-accent"
        >
          <span class="shrink-0 text-[15px] text-muted" aria-hidden="true">{{ publicHost }}</span>
          <input
            id="sign-up-slug"
            :value="form.slug"
            class="h-full min-w-0 flex-1 rounded-r-full bg-transparent pr-5 text-[15px] text-ink focus:outline-none"
            autocomplete="off"
            autocapitalize="none"
            spellcheck="false"
            :maxlength="SLUG_MAX_LENGTH"
            aria-describedby="sign-up-slug-status"
            @input="editSlug(($event.target as HTMLInputElement).value)"
          />
        </div>
        <p
          id="sign-up-slug-status"
          class="mt-1.5 pl-2 text-xs font-semibold"
          :class="slugStatus === 'available' ? 'text-success' : slugStatus === 'checking' ? 'text-muted' : 'text-danger'"
          role="status"
        >
          {{ slugMessage }}
          <button v-if="slugSuggestion" type="button" class="ml-1 text-accent underline" @click="editSlug(slugSuggestion)">
            {{ t('signUp.slug.use', { slug: slugSuggestion }) }}
          </button>
        </p>
      </div>
      <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!canFinish">
        {{ isBusy ? t('auth.checking') : t('signUp.finish') }}
      </BaseButton>
      <button type="button" class="text-sm font-semibold text-muted hover:text-ink" @click="step = 'code'">{{ t('signUp.back') }}</button>
    </form>

    <template #footer>
      <RouterLink :to="{ name: 'login' }" class="mt-6 text-center text-sm font-semibold text-muted hover:text-ink">
        {{ t('signUp.haveAccount') }}
      </RouterLink>
    </template>
  </AuthCardLayout>
</template>
