<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import ChoiceChips from '@/components/ui/ChoiceChips.vue'
import ServiceRow from './ServiceRow.vue'
import SettingsCard from './SettingsCard.vue'
import VacationLockCard from './VacationLockCard.vue'
import WorkingHoursEditor from './WorkingHoursEditor.vue'
import { useSettingsStore } from './settings.store'
import { BOOKING_WINDOW_OPTIONS, SLOT_INTERVAL_OPTIONS, SettingsSection } from './settings.model'
import { useSectionErrors } from './useSectionErrors'
import type { Service } from '@/Booking/booking.model'

const DEFAULT_NEW_SERVICE_DURATION = 30

const { t } = useI18n()
const store = useSettingsStore()
const { errorFor } = useSectionErrors(SettingsSection.Schedule)

const schedule = computed(() => store.drafts.schedule)

const intervalOptions = SLOT_INTERVAL_OPTIONS.map((minutes) => ({
  value: minutes as number,
  label: t('settings.schedule.minutes', { count: minutes }),
}))
const windowOptions = BOOKING_WINDOW_OPTIONS.map((days) => ({
  value: days as number,
  label: t('settings.schedule.days', { count: days }),
}))

function serviceErrors(index: number) {
  return {
    name: errorFor(`services.${index}.name`),
    durationMinutes: errorFor(`services.${index}.durationMinutes`),
    priceInCents: errorFor(`services.${index}.priceInCents`),
    costInCents: errorFor(`services.${index}.costInCents`),
  }
}

function updateService(index: number, patch: Partial<Service>): void {
  schedule.value.services = schedule.value.services.map((service, position) =>
    position === index ? { ...service, ...patch } : service,
  )
}

function addService(): void {
  schedule.value.services = [
    ...schedule.value.services,
    { id: `service-${Date.now()}`, name: '', durationMinutes: DEFAULT_NEW_SERVICE_DURATION, priceInCents: 0 },
  ]
}

function removeService(index: number): void {
  schedule.value.services = schedule.value.services.filter((_, position) => position !== index)
}
</script>

<template>
  <div class="grid gap-5 lg:max-w-2xl">
    <SettingsCard :title="t('settings.schedule.servicesTitle')" :description="t('settings.schedule.servicesDescription')">
      <ServiceRow
        v-for="(service, index) in schedule.services"
        :key="service.id"
        :service="service"
        :can-remove="schedule.services.length > 1"
        :errors="serviceErrors(index)"
        @update="updateService(index, $event)"
        @remove="removeService(index)"
      />
      <p v-if="errorFor('services')" class="text-sm text-danger" role="alert">{{ errorFor('services') }}</p>
      <BaseButton variant="secondary" block @click="addService">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
          <path d="M12 5v14M5 12h14" />
        </svg>
        {{ t('settings.schedule.addService') }}
      </BaseButton>
    </SettingsCard>

    <SettingsCard :title="t('settings.schedule.hoursTitle')" :description="t('settings.schedule.hoursDescription')">
      <WorkingHoursEditor v-model="schedule.weeklyHours" :error-for="errorFor" />
    </SettingsCard>

    <SettingsCard :title="t('settings.schedule.bookingTitle')">
      <ChoiceChips v-model="schedule.slotIntervalMinutes" :options="intervalOptions" :label="t('settings.schedule.slotInterval')" />
      <ChoiceChips v-model="schedule.bookingWindowDays" :options="windowOptions" :label="t('settings.schedule.bookingWindow')" />
    </SettingsCard>

    <SettingsCard :title="t('settings.schedule.vacationTitle')">
      <VacationLockCard v-model="schedule.vacation" :error-for="errorFor" />
    </SettingsCard>
  </div>
</template>
