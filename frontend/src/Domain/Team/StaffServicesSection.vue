<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import SettingsCard from '@/Domain/Settings/SettingsCard.vue'
import type { Service } from '@/Domain/Settings/settings.model'

/** 2. Serviços: which of the unit's services this barber does. */
defineProps<{ services: Service[] }>()
const serviceIds = defineModel<number[]>({ required: true })
const { t } = useI18n()

function toggle(serviceId: number, isOn: boolean): void {
  serviceIds.value = isOn ? [...serviceIds.value, serviceId] : serviceIds.value.filter((id) => id !== serviceId)
}
</script>

<template>
  <SettingsCard :title="t('team.agenda.services.title')" :description="t('team.agenda.services.description')">
    <p v-if="serviceIds.length === 0" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="status">
      {{ t('team.agenda.services.noneWarning') }}
    </p>
    <ToggleSwitch
      v-for="service in services"
      :key="service.id"
      :model-value="serviceIds.includes(Number(service.id))"
      :label="service.name"
      @update:model-value="toggle(Number(service.id), $event)"
    />
  </SettingsCard>
</template>
