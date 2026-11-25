<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { acceptStaffInvite, fetchStaffInvite } from './team.api'
import type { StaffInvitePreview } from './team.model'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const token = String(route.params.token ?? '')
const preview = ref<StaffInvitePreview | null>(null)
const loading = ref(true)
const error = ref('')
const name = ref('')
const password = ref('')
const submitting = ref(false)

onMounted(async () => {
  try {
    preview.value = await fetchStaffInvite(token)
    name.value = preview.value.name
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    loading.value = false
  }
})

async function submit(): Promise<void> {
  if (!name.value.trim() || password.value.length < 8) return
  submitting.value = true
  error.value = ''
  try {
    const session = await acceptStaffInvite(token, name.value.trim(), password.value)
    auth.startSession(session)
    await router.replace('/painel')
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto flex min-h-dvh max-w-md flex-col justify-center px-4 py-10">
    <h1 class="text-2xl font-bold">{{ t('team.acceptTitle') }}</h1>
    <p v-if="loading" class="mt-3 text-sm text-muted">{{ t('team.loading') }}</p>
    <template v-else-if="preview">
      <p class="mt-2 text-sm text-muted">
        {{ t('team.acceptSubtitle', { unit: preview.unitName, email: preview.email }) }}
      </p>
      <p
        v-if="preview.expired || preview.accepted"
        class="mt-4 rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger"
        role="alert"
      >
        {{ preview.accepted ? t('team.inviteAlreadyUsed') : t('team.inviteExpired') }}
      </p>
      <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
        <TextField v-model="name" :label="t('team.inviteName')" autocomplete="name" required />
        <TextField
          v-model="password"
          :label="t('team.password')"
          type="password"
          autocomplete="new-password"
          required
        />
        <p v-if="error" class="text-sm font-semibold text-danger" role="alert">{{ error }}</p>
        <BaseButton type="submit" class="w-full" :disabled="submitting">{{ t('team.acceptAction') }}</BaseButton>
      </form>
    </template>
    <p v-else-if="error" class="mt-4 text-sm font-semibold text-danger" role="alert">{{ error }}</p>
  </div>
</template>
