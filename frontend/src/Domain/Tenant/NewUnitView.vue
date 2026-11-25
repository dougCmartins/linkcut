<script setup lang="ts">
import { computed, reactive, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import SlugField from '@/Domain/Onboarding/SlugField.vue'
import { slugify } from '@/Domain/Onboarding/slug'
import { useSlugCheck } from '@/Domain/Onboarding/useSlugCheck'
import AddressEditor from '@/Domain/Settings/AddressEditor.vue'
import SettingsCard from '@/Domain/Settings/SettingsCard.vue'
import { emptyAddress } from '@/Domain/Settings/settings.model'
import { TEXT_LIMITS, validateAddress } from '@/Domain/Settings/settings.validation'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { useOrganizationStore } from './organization.store'

/**
 * "Nova filial" (owner only, the route guard sends a barber back to the panel). Name, public link (follows the
 * name until edited, checked while typing), the address, and whether to copy the main unit's settings once.
 * After saving, the panel switches to the new unit and shows its link.
 */
const { t } = useI18n()
const router = useRouter()
const apiErrors = useApiErrors()
const organization = useOrganizationStore()

const form = reactive({ name: '', slug: '', address: emptyAddress(), copySettings: false })
const savedAddress = emptyAddress()
const slugEdited = ref(false)
const { status: slugStatus, suggestion: slugSuggestion, recheck: recheckSlug } = useSlugCheck(toRef(form, 'slug'))
const showErrors = ref(false)
const isSaving = ref(false)
const error = ref('')

watch(
  () => form.name,
  (name) => {
    if (!slugEdited.value) form.slug = slugify(name)
  },
)

const nameError = computed(() => {
  const length = form.name.trim().length
  if (length === 0) return t('settings.errors.required')
  if (length < TEXT_LIMITS.name.min) return t('settings.errors.tooShort', { min: TEXT_LIMITS.name.min })
  return undefined
})
const addressErrors = computed(() => validateAddress(form.address))

function errorFor(field: string): string | undefined {
  const found = showErrors.value ? addressErrors.value[field] : undefined
  return found ? t(`settings.errors.${found.key}`, found.params ?? {}) : undefined
}

const isValid = computed(
  () => !nameError.value && slugStatus.value === 'available' && Object.keys(addressErrors.value).length === 0,
)

async function save(): Promise<void> {
  showErrors.value = true
  if (!isValid.value || isSaving.value) return
  isSaving.value = true
  error.value = ''
  try {
    const unit = await organization.createUnit({
      name: form.name,
      slug: form.slug,
      address: form.address,
      copySettings: form.copySettings,
    })
    await organization.selectUnit(unit.id)
    await router.push({ name: 'dashboard', query: { filial: unit.slug } })
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.SlugUnavailable)) recheckSlug()
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <main class="mx-auto w-full max-w-lg px-4 pt-6 pb-16">
    <RouterLink :to="{ name: 'settings' }" class="text-sm font-semibold text-accent">← {{ t('organization.newUnit.back') }}</RouterLink>
    <h1 class="mt-2 text-2xl leading-tight">{{ t('organization.newUnit.title') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ t('organization.newUnit.subtitle') }}</p>

    <form class="mt-6 grid gap-5" novalidate @submit.prevent="save">
      <SettingsCard :title="t('organization.newUnit.identityTitle')">
        <TextField
          v-model="form.name"
          :label="t('organization.newUnit.name')"
          :maxlength="TEXT_LIMITS.name.max"
          :error="showErrors ? nameError : undefined"
        />
        <SlugField
          id="new-unit-slug"
          v-model="form.slug"
          :label="t('organization.newUnit.slug')"
          :status="slugStatus"
          :suggestion="slugSuggestion"
          @edit="slugEdited = true"
        />
      </SettingsCard>

      <SettingsCard :title="t('settings.address.title')" :description="t('organization.newUnit.addressDescription')">
        <AddressEditor v-model="form.address" :saved="savedAddress" :error-for="errorFor" />
      </SettingsCard>

      <SettingsCard :title="t('organization.newUnit.copyTitle')">
        <ToggleSwitch
          v-model="form.copySettings"
          :label="t('organization.newUnit.copySettings')"
          :description="t('organization.newUnit.copySettingsHint')"
        />
      </SettingsCard>

      <p v-if="error" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

      <BaseButton type="submit" elevated block :disabled="isSaving">
        {{ isSaving ? t('organization.newUnit.saving') : t('organization.newUnit.submit') }}
      </BaseButton>
    </form>
  </main>
</template>
