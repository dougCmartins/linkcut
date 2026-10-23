<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { isValidBrazilianPhone } from '@/utils/phone'
import PrivacyLinks from '@/Legal/PrivacyLinks.vue'
import ClientLayout from './ClientLayout.vue'
import { CLIENT_LOGIN_ROUTE } from './client.guard'
import { useClientStore } from './client.store'

/**
 * /conta/perfil ("Minha conta"): name and WhatsApp (filled in the next bookings), e-mail reminders on/off,
 * new password and deleting the account (LGPD). Each block saves on its own.
 */
const PASSWORD_MIN_LENGTH = 8
const NAME_MIN_LENGTH = 2

const { t } = useI18n()
const router = useRouter()
const apiErrors = useApiErrors()
const clientStore = useClientStore()

type Feedback = { kind: 'success' | 'error'; message: string } | null
const errorOf = (caught: unknown) => t(`errors.api.${apiErrors.messageKey(caught)}`)

// Name and WhatsApp
const profile = reactive({ name: clientStore.client?.name ?? '', phone: clientStore.client?.phone ?? '' })
const profileBusy = ref(false)
const profileFeedback = ref<Feedback>(null)
const phoneError = computed(() => (profile.phone && !isValidBrazilianPhone(profile.phone) ? t('client.profile.phoneInvalid') : undefined))
const canSaveProfile = computed(
  () => profile.name.trim().length >= NAME_MIN_LENGTH && isValidBrazilianPhone(profile.phone) && !profileBusy.value,
)

async function saveProfile(): Promise<void> {
  if (!canSaveProfile.value) return
  profileBusy.value = true
  profileFeedback.value = null
  try {
    await clientStore.updateProfile({ name: profile.name.trim(), phone: profile.phone })
    profileFeedback.value = { kind: 'success', message: t('client.profile.saved') }
  } catch (caught) {
    profileFeedback.value = { kind: 'error', message: apiErrors.hasFieldError(caught, 'phone') ? t('client.profile.phoneInvalid') : errorOf(caught) }
  } finally {
    profileBusy.value = false
  }
}

// E-mail reminders: saved on toggle; back to the previous value when the API refuses.
const emailsFeedback = ref('')
const emailsEnabled = computed({
  get: () => clientStore.client?.emailsEnabled ?? true,
  set: (enabled: boolean) => void setEmails(enabled),
})
async function setEmails(enabled: boolean): Promise<void> {
  emailsFeedback.value = ''
  try {
    await clientStore.setEmailsEnabled(enabled)
  } catch (caught) {
    emailsFeedback.value = errorOf(caught)
  }
}

// New password
const password = reactive({ current: '', next: '' })
const passwordBusy = ref(false)
const passwordFeedback = ref<Feedback>(null)
const canChangePassword = computed(() => password.current.length > 0 && password.next.length >= PASSWORD_MIN_LENGTH && !passwordBusy.value)

async function changePassword(): Promise<void> {
  if (!canChangePassword.value) return
  passwordBusy.value = true
  passwordFeedback.value = null
  try {
    await clientStore.changePassword({ currentPassword: password.current, password: password.next })
    password.current = ''
    password.next = ''
    passwordFeedback.value = { kind: 'success', message: t('client.profile.passwordChanged') }
  } catch (caught) {
    const wrong = apiErrors.hasFieldError(caught, 'current_password')
    passwordFeedback.value = { kind: 'error', message: wrong ? t('client.profile.wrongPassword') : errorOf(caught) }
  } finally {
    passwordBusy.value = false
  }
}

// Delete the account (two steps: open, then confirm with the password)
const isDeleting = ref(false)
const deletePassword = ref('')
const deleteBusy = ref(false)
const deleteError = ref('')

async function deleteAccount(): Promise<void> {
  if (!deletePassword.value || deleteBusy.value) return
  deleteBusy.value = true
  deleteError.value = ''
  try {
    await clientStore.deleteAccount(deletePassword.value)
    await router.replace({ name: CLIENT_LOGIN_ROUTE, query: { conta: 'excluida' } })
  } catch (caught) {
    deleteError.value = apiErrors.hasFieldError(caught, 'password') ? t('client.profile.wrongPassword') : errorOf(caught)
  } finally {
    deleteBusy.value = false
  }
}

const feedbackClass = (feedback: Feedback) => (feedback?.kind === 'success' ? 'text-success' : 'text-danger')
</script>

<template>
  <ClientLayout :title="t('client.profile.title')">
    <div class="grid gap-5">
      <BaseCard class="grid grid-cols-1 gap-4 p-5">
        <h2 class="text-base">{{ t('client.profile.dataTitle') }}</h2>
        <form class="grid grid-cols-1 gap-4" novalidate @submit.prevent="saveProfile">
          <TextField v-model="profile.name" :label="t('client.profile.name')" autocomplete="name" :maxlength="60" />
          <TextField
            v-model="profile.phone"
            :label="t('client.profile.phone')"
            type="tel"
            inputmode="tel"
            autocomplete="tel"
            :hint="t('client.profile.phoneHint')"
            :error="phoneError"
          />
          <p class="text-sm">
            <span class="block text-xs font-semibold text-muted">{{ t('auth.email') }}</span>
            <span class="break-all">{{ clientStore.client?.email }}</span>
          </p>
          <BaseButton type="submit" variant="primary" block :disabled="!canSaveProfile">
            {{ profileBusy ? t('client.profile.saving') : t('client.profile.save') }}
          </BaseButton>
          <p v-if="profileFeedback" class="text-sm font-semibold" :class="feedbackClass(profileFeedback)" role="status">
            {{ profileFeedback.message }}
          </p>
        </form>
      </BaseCard>

      <BaseCard class="grid grid-cols-1 gap-3 p-5">
        <ToggleSwitch v-model="emailsEnabled" :label="t('client.profile.emails')" :description="t('client.profile.emailsHint')" />
        <p v-if="emailsFeedback" class="text-sm font-semibold text-danger" role="alert">{{ emailsFeedback }}</p>
      </BaseCard>

      <BaseCard class="grid grid-cols-1 gap-4 p-5">
        <h2 class="text-base">{{ t('client.profile.passwordTitle') }}</h2>
        <form class="grid grid-cols-1 gap-4" novalidate @submit.prevent="changePassword">
          <TextField v-model="password.current" :label="t('client.profile.currentPassword')" type="password" autocomplete="current-password" />
          <TextField
            v-model="password.next"
            :label="t('client.profile.newPassword')"
            type="password"
            autocomplete="new-password"
            :hint="t('client.passwordHint', { min: PASSWORD_MIN_LENGTH })"
          />
          <BaseButton type="submit" variant="secondary" block :disabled="!canChangePassword">
            {{ t('client.profile.changePassword') }}
          </BaseButton>
          <p v-if="passwordFeedback" class="text-sm font-semibold" :class="feedbackClass(passwordFeedback)" role="status">
            {{ passwordFeedback.message }}
          </p>
        </form>
      </BaseCard>

      <BaseCard class="grid grid-cols-1 gap-3 p-5">
        <h2 class="text-base">{{ t('client.profile.privacyTitle') }}</h2>
        <p class="text-sm text-muted">{{ t('client.profile.privacyText') }}</p>
        <PrivacyLinks class="!justify-start" />
      </BaseCard>

      <BaseCard class="grid grid-cols-1 gap-3 p-5">
        <h2 class="text-base">{{ t('client.profile.deleteTitle') }}</h2>
        <p class="text-sm text-muted">{{ t('client.profile.deleteText') }}</p>
        <button v-if="!isDeleting" type="button" class="justify-self-start text-sm font-bold text-danger" @click="isDeleting = true">
          {{ t('client.profile.deleteOpen') }}
        </button>
        <form v-else class="grid grid-cols-1 gap-3" novalidate @submit.prevent="deleteAccount">
          <TextField v-model="deletePassword" :label="t('client.profile.deletePassword')" type="password" autocomplete="current-password" />
          <div class="flex flex-wrap items-center gap-3">
            <button
              type="submit"
              class="h-10 rounded-full bg-danger px-4 text-sm font-bold text-white disabled:opacity-60"
              :disabled="!deletePassword || deleteBusy"
            >
              {{ t('client.profile.deleteConfirm') }}
            </button>
            <button type="button" class="h-10 px-2 text-sm font-semibold text-muted hover:text-ink" @click="isDeleting = false">
              {{ t('client.profile.deleteKeep') }}
            </button>
          </div>
          <p v-if="deleteError" class="text-sm font-semibold text-danger" role="alert">{{ deleteError }}</p>
        </form>
      </BaseCard>
    </div>
  </ClientLayout>
</template>
