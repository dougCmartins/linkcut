<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AuthHeading from '@/components/ui/AuthHeading.vue'
import AuthSplitLayout from '@/components/ui/AuthSplitLayout.vue'
import AuthSwitch from '@/components/ui/AuthSwitch.vue'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { AUTH_PHOTOS } from '@/assets/auth/authPhotos'
import { credentialsSchema } from '@/Domain/Identity/auth.schemas'
import { useApiErrors } from '@/composables/useApiErrors'
import { usePageMeta } from '@/composables/usePageMeta'
import PublicPageStatus from '@/Domain/Settings/PublicPageStatus.vue'
import { usePublicPage } from '@/Domain/Settings/usePublicPage'
import ClientPasswordForm from './ClientPasswordForm.vue'
import ClientGoogleSignInLink from './ClientGoogleSignInLink.vue'
import { isGoogleLoginEnabled } from './googleLogin'
import { safeClientRedirect } from './client.guard'
import { CodePurpose } from './client.model'
import { useClientStore } from './client.store'

/**
 * The client's entrance, one page for /<slug>/login (with the barbershop's theme, name and photo), /conta/entrar
 * (no barbershop known on this device) and /conta/finalizar?email=… (first access: a code to the booking e-mail,
 * then the password). The routes share this component, so switching only swaps the form and the photo.
 */
type Mode = 'login' | 'register' | 'reset'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const apiErrors = useApiErrors()
const clientStore = useClientStore()

// /<slug>/login loads the barbershop (theme, name, photo); the other routes have no slug and load nothing.
const publicPage = usePublicPage()
const hasBarbershop = computed(() => typeof route.params.slug === 'string')
const barbershop = computed(() => (hasBarbershop.value ? (publicPage.page?.profile ?? null) : null))

const isResetting = ref(false)
const mode = computed<Mode>(() => (route.meta.clientAuthMode === 'register' ? 'register' : isResetting.value ? 'reset' : 'login'))
watch(
  () => route.meta.clientAuthMode,
  () => (isResetting.value = false),
)

const linkEmail = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''))
const keptQuery = computed(() => (typeof route.query.redirect === 'string' ? { redirect: route.query.redirect } : {}))
const photo = computed(() => {
  const url = barbershop.value?.backgroundUrl || (hasBarbershop.value ? publicPage.page?.portfolio.coverUrl : null)
  if (url) return { src: url, srcset: `${url} 1080w`, alt: t('barberLogin.photoAlt', { name: barbershop.value?.name ?? '' }) }
  return mode.value === 'register'
    ? { ...AUTH_PHOTOS.signUp, alt: t('authPage.photos.signUp') }
    : { ...AUTH_PHOTOS.login, alt: t('authPage.photos.login') }
})

const email = ref('')
const password = ref('')
const isBusy = ref(false)
const error = ref('')
const canSubmit = computed(() => credentialsSchema.safeParse({ email: email.value, password: password.value }).success && !isBusy.value)

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

usePageMeta(
  () => t(`authPage.client.${mode.value}.title`),
  () => t(`authPage.client.${mode.value}.subtitle`),
)
</script>

<template>
  <PublicPageStatus v-if="hasBarbershop && !barbershop" :status="publicPage.status" @retry="publicPage.reload()" />
  <AuthSplitLayout v-else :photo="photo" :photo-key="mode">
    <div v-if="barbershop" class="mb-6 flex items-center gap-3">
      <AvatarImage :src="barbershop.avatarUrl" :alt="t('bioLink.avatarAlt', { name: barbershop.name })" size="md" />
      <p class="font-display text-lg font-extrabold break-words">{{ barbershop.name }}</p>
    </div>
    <section v-if="mode === 'reset'">
      <AuthHeading :title="t('authPage.client.reset.title')" :subtitle="t('authPage.client.reset.subtitle')" />
      <ClientPasswordForm :purpose="CodePurpose.Password" :initial-email="email" @done="goOn" />
      <p class="mt-8 text-center text-sm">
        <button type="button" class="font-semibold text-accent underline underline-offset-4" @click="isResetting = false">
          {{ t('client.reset.back') }}
        </button>
      </p>
    </section>

    <section v-else-if="mode === 'register'">
      <AuthHeading :title="t('authPage.client.register.title')" :subtitle="t('authPage.client.register.subtitle')" />
      <ClientPasswordForm :purpose="CodePurpose.Registration" :initial-email="linkEmail" @done="goOn" />
      <div v-if="isGoogleLoginEnabled()" class="mt-5 grid gap-3">
        <p class="text-center text-xs font-semibold tracking-wide text-muted uppercase">{{ t('client.google.or') }}</p>
        <ClientGoogleSignInLink />
      </div>
      <AuthSwitch
        :question="t('authPage.client.haveAccount')"
        :action="t('authPage.client.loginAction')"
        :to="{ name: 'client-login', query: keptQuery }"
      />
    </section>

    <section v-else>
      <AuthHeading :title="t('authPage.client.login.title')" :subtitle="t('authPage.client.login.subtitle')" />
      <p
        v-if="route.query.conta === 'excluida'"
        class="mb-5 rounded-card bg-raised px-4 py-3 text-sm font-semibold ring-1 ring-line"
        role="status"
      >
        {{ t('client.profile.deleted') }}
      </p>
      <p
        v-if="route.query.erro === 'google'"
        class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40"
        role="alert"
      >
        {{ t('client.google.denied') }}
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
        <button type="button" class="text-sm font-semibold text-accent underline underline-offset-4" @click="isResetting = true">
          {{ t('client.login.forgot') }}
        </button>
      </form>
      <div v-if="isGoogleLoginEnabled()" class="mt-5 grid gap-3">
        <p class="text-center text-xs font-semibold tracking-wide text-muted uppercase">{{ t('client.google.or') }}</p>
        <ClientGoogleSignInLink />
      </div>
      <AuthSwitch
        :question="t('authPage.client.firstAccess')"
        :action="t('authPage.client.registerAction')"
        :to="{ name: 'client-register', query: keptQuery }"
      />
    </section>
  </AuthSplitLayout>
</template>
