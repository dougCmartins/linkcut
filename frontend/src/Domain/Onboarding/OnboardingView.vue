<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import TextField from '@/components/ui/TextField.vue'
import AddressEditor from '@/Domain/Settings/AddressEditor.vue'
import ServiceRow from '@/Domain/Settings/ServiceRow.vue'
import WorkingHoursEditor from '@/Domain/Settings/WorkingHoursEditor.vue'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { useSectionErrors } from '@/Domain/Settings/useSectionErrors'
import { NEW_SERVICE_ID_PREFIX, SaveResult, SettingsSection, type Service } from '@/Domain/Settings/settings.model'
import { useTenantStore } from '@/Domain/Tenant/tenant.store'
import { useApiErrors } from '@/composables/useApiErrors'

/**
 * /painel/boas-vindas: first run of a new barbershop. WhatsApp and address (required to take bookings) →
 * services and hours (defaults ready to edit) → the public page is live. Each step saves through the settings store.
 */
const STEPS = ['contact', 'services', 'done'] as const
type Step = (typeof STEPS)[number]
const DEFAULT_NEW_SERVICE_DURATION = 30

const { t } = useI18n()
const router = useRouter()
const settings = useSettingsStore()
const tenantStore = useTenantStore()
const apiErrors = useApiErrors()
const profileErrors = useSectionErrors(SettingsSection.Profile)
const scheduleErrors = useSectionErrors(SettingsSection.Schedule)

const step = ref<Step>('contact')
const isBusy = ref(false)
const error = ref('')
const copied = ref(false)
/** Field errors show only after the barber tries to go on (not on an untouched form). */
const triedSteps = ref<Step[]>([])

const profile = computed(() => settings.drafts.profile)
const schedule = computed(() => settings.drafts.schedule)
const publicPath = computed(() => (tenantStore.tenant ? `/${tenantStore.tenant.slug}` : ''))
const publicUrl = computed(() => `${window.location.host}${publicPath.value}`)

const errorFor = (current: Step, errors: typeof profileErrors) => (field: string) =>
  triedSteps.value.includes(current) ? errors.errorFor(field) : undefined
const contactErrorFor = errorFor('contact', profileErrors)
const servicesErrorFor = errorFor('services', scheduleErrors)

function serviceErrors(index: number) {
  return {
    name: servicesErrorFor(`services.${index}.name`),
    durationMinutes: servicesErrorFor(`services.${index}.durationMinutes`),
    priceInCents: servicesErrorFor(`services.${index}.priceInCents`),
    costInCents: servicesErrorFor(`services.${index}.costInCents`),
  }
}

function updateService(index: number, patch: Partial<Service>): void {
  schedule.value.services = schedule.value.services.map((service, position) => (position === index ? { ...service, ...patch } : service))
}

function addService(): void {
  schedule.value.services = [
    ...schedule.value.services,
    { id: `${NEW_SERVICE_ID_PREFIX}${Date.now()}`, name: '', durationMinutes: DEFAULT_NEW_SERVICE_DURATION, priceInCents: 0 },
  ]
}

function removeService(index: number): void {
  schedule.value.services = schedule.value.services.filter((_, position) => position !== index)
}

/** Saves the section of the step and moves on; invalid fields stay on screen with their messages. */
async function saveAndContinue(section: SettingsSection, current: Step, next: Step): Promise<void> {
  if (!triedSteps.value.includes(current)) triedSteps.value = [...triedSteps.value, current]
  isBusy.value = true
  error.value = ''
  try {
    if ((await settings.save(section)) === SaveResult.Invalid) {
      error.value = t('onboarding.fixFields')
      return
    }
    step.value = next
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
  } finally {
    isBusy.value = false
  }
}

async function copyLink(): Promise<void> {
  try {
    await navigator.clipboard.writeText(`${window.location.origin}${publicPath.value}`)
    copied.value = true
  } catch {
    copied.value = false
  }
}

async function finish(): Promise<void> {
  isBusy.value = true
  error.value = ''
  try {
    await tenantStore.finishOnboarding()
    await router.replace('/painel')
  } catch (caught) {
    error.value = t(`errors.api.${apiErrors.messageKey(caught)}`)
  } finally {
    isBusy.value = false
  }
}

onMounted(() => {
  void settings.ensureLoaded()
  void tenantStore.ensureLoaded()
})
</script>

<template>
  <main class="mx-auto w-full max-w-xl px-5 py-8">
    <ol class="mb-6 flex gap-1.5" :aria-label="t('onboarding.progress')">
      <li
        v-for="(item, index) in STEPS"
        :key="item"
        class="h-1.5 flex-1 rounded-full transition"
        :class="STEPS.indexOf(step) >= index ? 'bg-accent' : 'bg-raised'"
        :aria-current="item === step ? 'step' : undefined"
      />
    </ol>
    <h1 class="text-2xl leading-tight">{{ t(`onboarding.steps.${step}.title`) }}</h1>
    <p class="mt-1.5 mb-6 text-sm text-muted">{{ t(`onboarding.steps.${step}.subtitle`) }}</p>

    <p v-if="error" class="mb-5 rounded-card bg-danger/10 px-4 py-3 text-sm font-semibold text-danger ring-1 ring-danger/40" role="alert">
      {{ error }}
    </p>

    <p v-if="!settings.isLoaded" class="text-sm text-muted" role="status">{{ t('onboarding.loading') }}</p>

    <div v-else-if="step === 'contact'" class="grid gap-5">
      <BaseCard class="grid gap-4 p-5">
        <TextField
          v-model="profile.whatsappNumber"
          type="tel"
          inputmode="tel"
          autocomplete="tel"
          :label="t('onboarding.whatsapp')"
          :hint="t('settings.link.whatsappHint')"
          :error="contactErrorFor('whatsappNumber')"
        />
      </BaseCard>
      <BaseCard class="grid gap-4 p-5">
        <h2 class="text-base">{{ t('onboarding.addressTitle') }}</h2>
        <AddressEditor v-model="profile.address" :saved="settings.profile.address" :error-for="contactErrorFor" />
      </BaseCard>
      <BaseButton
        variant="primary"
        size="lg"
        elevated
        block
        :disabled="isBusy"
        @click="saveAndContinue(SettingsSection.Profile, 'contact', 'services')"
      >
        {{ t('onboarding.next') }}
      </BaseButton>
    </div>

    <div v-else-if="step === 'services'" class="grid gap-5">
      <BaseCard class="grid gap-4 p-5">
        <h2 class="text-base">{{ t('settings.schedule.servicesTitle') }}</h2>
        <ServiceRow
          v-for="(service, index) in schedule.services"
          :key="service.id"
          :service="service"
          :can-remove="schedule.services.length > 1"
          :errors="serviceErrors(index)"
          @update="updateService(index, $event)"
          @remove="removeService(index)"
        />
        <BaseButton variant="secondary" block @click="addService">{{ t('settings.schedule.addService') }}</BaseButton>
      </BaseCard>
      <BaseCard class="grid gap-4 p-5">
        <h2 class="text-base">{{ t('settings.schedule.hoursTitle') }}</h2>
        <WorkingHoursEditor v-model="schedule.weeklyHours" :error-for="servicesErrorFor" />
      </BaseCard>
      <BaseButton
        variant="primary"
        size="lg"
        elevated
        block
        :disabled="isBusy"
        @click="saveAndContinue(SettingsSection.Schedule, 'services', 'done')"
      >
        {{ t('onboarding.next') }}
      </BaseButton>
      <button type="button" class="text-sm font-semibold text-muted hover:text-ink" @click="step = 'contact'">
        {{ t('onboarding.back') }}
      </button>
    </div>

    <div v-else class="grid gap-5">
      <BaseCard class="grid gap-3 p-5 text-center">
        <p class="text-sm text-muted">{{ t('onboarding.linkLabel') }}</p>
        <p class="font-display text-lg font-extrabold break-all">{{ publicUrl }}</p>
        <div class="grid gap-2 sm:grid-cols-2">
          <BaseButton variant="secondary" block @click="copyLink">{{ copied ? t('onboarding.copied') : t('onboarding.copy') }}</BaseButton>
          <BaseButton variant="secondary" block :to="publicPath">{{ t('onboarding.viewPage') }}</BaseButton>
        </div>
        <p class="text-xs text-muted">{{ t('onboarding.shareHint') }}</p>
      </BaseCard>
      <BaseButton variant="primary" size="lg" elevated block :disabled="isBusy" @click="finish">{{ t('onboarding.finish') }}</BaseButton>
    </div>
  </main>
</template>
