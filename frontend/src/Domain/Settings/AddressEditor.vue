<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import { GeolocationStatus, useGeolocation } from '@/composables/useGeolocation'
import { formatZipCode } from '@/utils/address'
import { fetchAddressByZipCode } from './cep.api'
import { BRAZIL_STATES, type BarbershopAddress } from './settings.model'
import { TEXT_LIMITS } from './settings.validation'

type AddressField = Exclude<keyof BarbershopAddress, 'latitude' | 'longitude'>

/**
 * The barbershop address (required). The CEP fills street, district, city and state.
 * Any change clears the point on the map, so the API finds it again when saving;
 * "Estou na barbearia" uses the device location as the point instead.
 */
const props = defineProps<{ saved: BarbershopAddress; errorFor: (field: string) => string | undefined }>()
const address = defineModel<BarbershopAddress>({ required: true })
const { t } = useI18n()
const geolocation = useGeolocation()

const ZIP_CODE_LENGTH = 8
const stateOptions = BRAZIL_STATES.map((state) => ({ value: state, label: state }))
const zipCodeStatus = ref<'idle' | 'loading' | 'notFound' | 'failed'>('idle')

function change(field: AddressField, value: string): void {
  address.value = { ...address.value, [field]: value, latitude: null, longitude: null }
}

async function changeZipCode(value: string): Promise<void> {
  const digits = value.replace(/\D/g, '').slice(0, ZIP_CODE_LENGTH)
  change('zipCode', digits)
  zipCodeStatus.value = 'idle'
  if (digits.length !== ZIP_CODE_LENGTH) return
  zipCodeStatus.value = 'loading'
  try {
    const found = await fetchAddressByZipCode(digits)
    if (address.value.zipCode !== digits) return
    if (!found) {
      zipCodeStatus.value = 'notFound'
      return
    }
    address.value = { ...address.value, ...found, latitude: null, longitude: null }
    zipCodeStatus.value = 'idle'
  } catch {
    zipCodeStatus.value = 'failed'
  }
}

async function useDeviceLocation(): Promise<void> {
  const point = await geolocation.request()
  if (point) address.value = { ...address.value, ...point }
}

const zipCodeHint = computed(() => {
  if (zipCodeStatus.value === 'loading') return t('settings.address.zipCodeLoading')
  if (zipCodeStatus.value === 'failed') return t('settings.address.zipCodeFailed')
  return t('settings.address.zipCodeHint')
})
const zipCodeError = computed(() =>
  zipCodeStatus.value === 'notFound' ? t('settings.address.zipCodeNotFound') : props.errorFor('address.zipCode'),
)

const isSaved = computed(() => JSON.stringify(address.value) === JSON.stringify(props.saved))
const pointMessage = computed(() => {
  if (geolocation.status.value === GeolocationStatus.Denied) return { text: t('settings.address.locationDenied'), isWarning: true }
  if (geolocation.status.value === GeolocationStatus.Unavailable) return { text: t('settings.address.locationUnavailable'), isWarning: true }
  if (address.value.latitude !== null) return { text: t('settings.address.pointSet'), isWarning: false }
  if (isSaved.value && address.value.street) return { text: t('settings.address.pointMissing'), isWarning: true }
  return { text: t('settings.address.pointOnSave'), isWarning: false }
})
</script>

<template>
  <div class="grid gap-4">
    <TextField
      :model-value="formatZipCode(address.zipCode)"
      :label="t('settings.address.zipCode')"
      inputmode="numeric"
      autocomplete="postal-code"
      :maxlength="9"
      :hint="zipCodeHint"
      :error="zipCodeError"
      @update:model-value="changeZipCode"
    />
    <div class="grid grid-cols-[minmax(0,1fr)_7rem] gap-3">
      <TextField
        :model-value="address.street"
        :label="t('settings.address.street')"
        autocomplete="address-line1"
        :maxlength="TEXT_LIMITS.addressStreet.max"
        :error="errorFor('address.street')"
        @update:model-value="change('street', $event)"
      />
      <TextField
        :model-value="address.number"
        :label="t('settings.address.number')"
        :maxlength="TEXT_LIMITS.addressNumber.max"
        :error="errorFor('address.number')"
        @update:model-value="change('number', $event)"
      />
    </div>
    <TextField
      :model-value="address.complement"
      :label="t('settings.address.complement')"
      autocomplete="address-line2"
      :maxlength="TEXT_LIMITS.addressComplement.max"
      :error="errorFor('address.complement')"
      @update:model-value="change('complement', $event)"
    />
    <TextField
      :model-value="address.district"
      :label="t('settings.address.district')"
      :maxlength="TEXT_LIMITS.addressDistrict.max"
      :error="errorFor('address.district')"
      @update:model-value="change('district', $event)"
    />
    <div class="grid grid-cols-[minmax(0,1fr)_7rem] gap-3">
      <TextField
        :model-value="address.city"
        :label="t('settings.address.city')"
        autocomplete="address-level2"
        :maxlength="TEXT_LIMITS.addressCity.max"
        :error="errorFor('address.city')"
        @update:model-value="change('city', $event)"
      />
      <SelectField
        :model-value="address.state"
        :label="t('settings.address.state')"
        :options="stateOptions"
        :placeholder="t('settings.address.statePlaceholder')"
        :error="errorFor('address.state')"
        @update:model-value="change('state', $event)"
      />
    </div>

    <div class="rounded-card bg-raised p-4 ring-1 ring-line">
      <p class="text-sm" :class="pointMessage.isWarning ? 'font-semibold text-danger' : 'text-muted'" role="status">
        {{ pointMessage.text }}
      </p>
      <BaseButton
        class="mt-3"
        variant="secondary"
        block
        :disabled="geolocation.status.value === GeolocationStatus.Asking"
        @click="useDeviceLocation"
      >
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="M12 21s-7-6.2-7-11.5a7 7 0 1 1 14 0C19 14.8 12 21 12 21Z" />
          <circle cx="12" cy="9.5" r="2.5" />
        </svg>
        {{ geolocation.status.value === GeolocationStatus.Asking ? t('settings.address.locating') : t('settings.address.useMyLocation') }}
      </BaseButton>
      <p class="mt-2 text-xs text-muted">{{ t('settings.address.useMyLocationHint') }}</p>
    </div>
  </div>
</template>
