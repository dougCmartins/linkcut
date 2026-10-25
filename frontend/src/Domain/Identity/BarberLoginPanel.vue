<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AuthHeading from '@/components/ui/AuthHeading.vue'
import ChannelPicker from './ChannelPicker.vue'
import CodeForm from './CodeForm.vue'
import CredentialsForm from './CredentialsForm.vue'
import { safeRedirect } from './auth.guard'
import { LoginStep, type Credentials, type TwoFactorChannel } from './auth.model'
import { useAuthStore } from './auth.store'
import { useSettingsStore } from '@/Domain/Settings/settings.store'

/** Barber sign-in (on /entrar): e-mail and password, then the code of the second step. */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

/** Demo account created by the backend seeder; only offered in development. */
const DEMO_ACCOUNT: Credentials = { email: 'rafael@linkcut.app', password: 'linkcut123' }
const demoAccount: Credentials | null = import.meta.env.DEV ? DEMO_ACCOUNT : null

const errorMessage = computed(() => {
  if (!auth.error) return ''
  const { code, attemptsLeft } = auth.error
  return t(`errors.api.${code}`, { attempts: attemptsLeft ?? 0 })
})

const stepTitle = computed(() => t(`auth.steps.${auth.step}.title`))
const stepSubtitle = computed(() => t(`auth.steps.${auth.step}.subtitle`))

function submitCredentials(credentials: Credentials): void {
  void auth.submitCredentials(credentials)
}

function chooseChannel(channel: TwoFactorChannel): void {
  void auth.requestCode(channel)
}

/** Signed in and on the way to the dashboard: the code step stays on screen ("Verificando…") until it opens. */
const isEntering = ref(false)

async function submitCode(code: string): Promise<void> {
  if (!(await auth.submitCode(code))) return
  isEntering.value = true
  // Starts the dashboard's settings (name, theme) and its code while the guard checks the barbershop.
  void useSettingsStore().ensureLoaded()
  void import('@/Dashboard/DashboardView.vue')
  try {
    await router.replace(safeRedirect(route.query.redirect))
  } finally {
    isEntering.value = false
  }
}

// The next visit to the login starts from the first step again.
onBeforeUnmount(() => {
  if (auth.session) auth.restart()
})
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

    <ol class="mb-6 flex max-w-40 gap-1.5" :aria-label="t('auth.progress')">
      <li
        v-for="(item, index) in Object.values(LoginStep)"
        :key="item"
        class="h-1.5 flex-1 rounded-full transition"
        :class="Object.values(LoginStep).indexOf(auth.step) >= index ? 'bg-accent' : 'bg-raised'"
        :aria-current="item === auth.step ? 'step' : undefined"
      />
    </ol>

    <AuthHeading :title="stepTitle" :subtitle="stepSubtitle" />

    <p
      v-if="errorMessage"
      class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40"
      role="alert"
    >
      {{ errorMessage }}
    </p>

    <CredentialsForm
      v-if="auth.step === LoginStep.Credentials"
      :is-busy="auth.isBusy"
      :demo-account="demoAccount"
      @submit="submitCredentials"
    />
    <ChannelPicker
      v-else-if="auth.step === LoginStep.Channel && auth.challenge"
      :destinations="auth.challenge.destinations"
      :is-busy="auth.isBusy"
      @choose="chooseChannel"
      @back="auth.restart()"
    />
    <CodeForm
      v-else-if="auth.step === LoginStep.Code && auth.delivery"
      :delivery="auth.delivery"
      :error="auth.error"
      :is-busy="auth.isBusy || isEntering"
      @submit="submitCode"
      @resend="auth.resendCode()"
      @change-channel="auth.changeChannel()"
    />

    <!-- "Ainda não tem conta? Criar", only on the first step. -->
    <slot v-if="auth.step === LoginStep.Credentials" name="switch" />
  </section>
</template>
