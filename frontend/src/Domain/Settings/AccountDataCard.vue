<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useTenantStore } from '@/Domain/Tenant/tenant.store'
import { useApiErrors } from '@/composables/useApiErrors'
import { downloadJson } from '@/utils/download'
import { deleteBarbershop, exportBarbershopData } from './account.api'
import SettingsCard from './SettingsCard.vue'

/**
 * "Seus dados" (LGPD): download everything of the barbershop as JSON, or delete the barbershop and everything in it
 * (asks the password and the page link typed again).
 */
const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const tenantStore = useTenantStore()
const apiErrors = useApiErrors()

const isExporting = ref(false)
const exportError = ref('')

async function download(): Promise<void> {
  isExporting.value = true
  exportError.value = ''
  try {
    downloadJson(`linkcut-${tenantStore.tenant?.slug ?? 'barbearia'}-dados.json`, await exportBarbershopData())
  } catch (caught) {
    exportError.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
  } finally {
    isExporting.value = false
  }
}

const isDeleting = ref(false)
const password = ref('')
const slugConfirmation = ref('')
const deleteBusy = ref(false)
const deleteError = ref('')
const slug = computed(() => tenantStore.tenant?.slug ?? '')
const canDelete = computed(
  () => password.value.length > 0 && slugConfirmation.value.trim().toLowerCase() === slug.value && !deleteBusy.value,
)

async function remove(): Promise<void> {
  if (!canDelete.value) return
  deleteBusy.value = true
  deleteError.value = ''
  try {
    await deleteBarbershop({ password: password.value, slugConfirmation: slugConfirmation.value })
    auth.forget()
    await router.replace({ path: '/entrar', query: { conta: 'excluida' } })
  } catch (caught) {
    deleteError.value = apiErrors.hasFieldError(caught, 'password')
      ? t('settings.account.wrongPassword')
      : apiErrors.hasFieldError(caught, 'slug_confirmation')
        ? t('settings.account.wrongSlug')
        : t(`errors.api.${apiErrors.messageKey(caught)}`)
  } finally {
    deleteBusy.value = false
  }
}

onMounted(() => void tenantStore.ensureLoaded())
</script>

<template>
  <SettingsCard :title="t('settings.account.title')" :description="t('settings.account.description')">
    <BaseButton variant="secondary" block :disabled="isExporting" @click="download">
      {{ isExporting ? t('settings.account.exporting') : t('settings.account.export') }}
    </BaseButton>
    <p v-if="exportError" class="text-sm font-semibold text-danger" role="alert">{{ exportError }}</p>

    <div class="border-t border-line pt-4">
      <h3 class="text-sm font-bold">{{ t('settings.account.deleteTitle') }}</h3>
      <p class="mt-1 text-sm text-muted">{{ t('settings.account.deleteText') }}</p>
      <button v-if="!isDeleting" type="button" class="mt-3 text-sm font-bold text-danger" @click="isDeleting = true">
        {{ t('settings.account.deleteOpen') }}
      </button>
      <form v-else class="mt-3 grid grid-cols-1 gap-3" novalidate @submit.prevent="remove">
        <TextField v-model="password" :label="t('settings.account.password')" type="password" autocomplete="current-password" />
        <TextField v-model="slugConfirmation" :label="t('settings.account.slugLabel', { slug })" autocomplete="off" />
        <div class="flex flex-wrap items-center gap-3">
          <button
            type="submit"
            class="h-10 rounded-full bg-danger px-4 text-sm font-bold text-white disabled:opacity-60"
            :disabled="!canDelete"
          >
            {{ t('settings.account.deleteConfirm') }}
          </button>
          <button type="button" class="h-10 px-2 text-sm font-semibold text-muted hover:text-ink" @click="isDeleting = false">
            {{ t('settings.account.deleteKeep') }}
          </button>
        </div>
        <p v-if="deleteError" class="text-sm font-semibold text-danger" role="alert">{{ deleteError }}</p>
      </form>
    </div>
  </SettingsCard>
</template>
