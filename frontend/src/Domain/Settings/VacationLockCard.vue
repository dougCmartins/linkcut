<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import TextArea from '@/components/ui/TextArea.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import type { VacationLock } from './settings.model'
import { TEXT_LIMITS } from './settings.validation'
import { formatDayMonth, fromIsoDate } from '@/utils/date'

defineProps<{ errorFor: (field: string) => string | undefined }>()
const vacation = defineModel<VacationLock>({ required: true })
const { t } = useI18n()

const formatDate = (isoDate: string) => formatDayMonth(fromIsoDate(isoDate))

const summary = computed(() => {
  const { startsOn, endsOn } = vacation.value
  if (startsOn && endsOn) return t('settings.schedule.vacationLockedRange', { from: formatDate(startsOn), to: formatDate(endsOn) })
  if (startsOn) return t('settings.schedule.vacationLockedFrom', { from: formatDate(startsOn) })
  if (endsOn) return t('settings.schedule.vacationLockedUntil', { to: formatDate(endsOn) })
  return t('settings.schedule.vacationLockedUntilOff')
})

function update(patch: Partial<VacationLock>): void {
  vacation.value = { ...vacation.value, ...patch }
}
</script>

<template>
  <div class="grid gap-4">
    <ToggleSwitch
      :model-value="vacation.isEnabled"
      :label="t('settings.schedule.vacationToggle')"
      :description="t('settings.schedule.vacationToggleDescription')"
      @update:model-value="update({ isEnabled: $event })"
    />
    <template v-if="vacation.isEnabled">
      <p class="rounded-card bg-accent/15 px-4 py-3 text-sm font-semibold ring-1 ring-accent/40" role="status">
        {{ summary }}
      </p>
      <div class="grid grid-cols-2 gap-3">
        <!-- Date inputs give "" when cleared; the model uses null for "no date". -->
        <TextField
          type="date"
          :model-value="vacation.startsOn ?? ''"
          :label="t('settings.schedule.vacationStartsOn')"
          @update:model-value="update({ startsOn: $event || null })"
        />
        <TextField
          type="date"
          :model-value="vacation.endsOn ?? ''"
          :label="t('settings.schedule.vacationEndsOn')"
          :error="errorFor('vacation.endsOn')"
          @update:model-value="update({ endsOn: $event || null })"
        />
      </div>
      <TextArea
        :model-value="vacation.message"
        :label="t('settings.schedule.vacationMessage')"
        :maxlength="TEXT_LIMITS.vacationMessage.max"
        :rows="2"
        :error="errorFor('vacation.message')"
        @update:model-value="update({ message: $event })"
      />
    </template>
  </div>
</template>
