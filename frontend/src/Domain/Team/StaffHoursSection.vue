<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SettingsCard from '@/Domain/Settings/SettingsCard.vue'
import WorkingHoursEditor from '@/Domain/Settings/WorkingHoursEditor.vue'
import type { StaffDayHours } from './staffSchedule.model'

/** 1. Horários: the barber's week, each open day with an optional break. */
defineProps<{ errorFor: (field: string) => string | undefined; isClosed: boolean }>()
const days = defineModel<StaffDayHours[]>({ required: true })
const { t } = useI18n()
</script>

<template>
  <SettingsCard :title="t('team.agenda.hours.title')" :description="t('team.agenda.hours.description')">
    <p v-if="isClosed" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="status">
      {{ t('team.agenda.hours.closedWarning') }}
    </p>
    <WorkingHoursEditor v-model="days" :error-for="errorFor" with-break />
  </SettingsCard>
</template>
