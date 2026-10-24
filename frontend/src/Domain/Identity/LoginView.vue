<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import BaseCard from '@/components/ui/BaseCard.vue'
import ChannelPicker from './ChannelPicker.vue'
import CodeForm from './CodeForm.vue'
import CredentialsForm from './CredentialsForm.vue'
import { safeRedirect } from './auth.guard'
import { LoginStep, type Credentials, type TwoFactorChannel } from './auth.model'
import { useAuthStore } from './auth.store'

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

async function submitCode(code: string): Promise<void> {
  if (await auth.submitCode(code)) await router.replace(safeRedirect(route.query.redirect))
}
</script>

<template>
  <main class="mx-auto flex min-h-dvh w-full max-w-md flex-col justify-center px-5 py-10">
    <RouterLink to="/" class="mx-auto mb-8 flex items-center gap-2 font-display text-xl font-extrabold tracking-tight">
      <span class="flex size-9 items-center justify-center rounded-xl bg-accent text-on-accent shadow-soft" aria-hidden="true">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="6" cy="6" r="3" />
          <circle cx="6" cy="18" r="3" />
          <path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12" />
        </svg>
      </span>
      LinkCut
    </RouterLink>

    <p
      v-if="route.query.conta === 'excluida'"
      class="mb-4 rounded-card bg-raised px-4 py-3 text-sm font-semibold ring-1 ring-line"
      role="status"
    >
      {{ t('settings.account.deleted') }}
    </p>
    <BaseCard floating class="p-6">
      <ol class="mb-5 flex gap-1.5" :aria-label="t('auth.progress')">
        <li
          v-for="(item, index) in Object.values(LoginStep)"
          :key="item"
          class="h-1.5 flex-1 rounded-full transition"
          :class="Object.values(LoginStep).indexOf(auth.step) >= index ? 'bg-accent' : 'bg-raised'"
          :aria-current="item === auth.step ? 'step' : undefined"
        />
      </ol>

      <h1 class="text-2xl leading-tight">{{ stepTitle }}</h1>
      <p class="mt-1.5 mb-6 text-sm text-muted">{{ stepSubtitle }}</p>

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
        :is-busy="auth.isBusy"
        @submit="submitCode"
        @resend="auth.resendCode()"
        @change-channel="auth.changeChannel()"
      />
    </BaseCard>

    <RouterLink to="/cadastrar" class="mt-6 text-center text-sm font-semibold text-accent">
      {{ t('auth.noAccount') }}
    </RouterLink>
    <RouterLink to="/" class="mt-3 text-center text-sm font-semibold text-muted hover:text-ink">
      {{ t('auth.backToPublic') }}
    </RouterLink>
  </main>
</template>
