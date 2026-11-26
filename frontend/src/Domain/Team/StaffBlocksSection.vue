<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import SettingsCard from '@/Domain/Settings/SettingsCard.vue'
import { formatWeekdayLong, toIsoDate } from '@/utils/date'
import { blockLabel } from './staffBlockLabel'
import type { NewStaffTimeBlock, StaffTimeBlock } from './staffSchedule.model'

/** 3. Bloqueios: once or every week; "Desbloquear" removes the whole block. */
defineProps<{ blocks: StaffTimeBlock[] }>()
const emit = defineEmits<{ add: [block: NewStaffTimeBlock]; remove: [blockId: number] }>()
const { t } = useI18n()

/** Monday first, Sunday last. */
const WEEK_ORDER = [1, 2, 3, 4, 5, 6, 0]

const isFormOpen = ref(false)
const showErrors = ref(false)
const form = reactive({
  date: toIsoDate(new Date()),
  startTime: '13:00',
  endTime: '18:00',
  /** Optional: a single block spanning days, or the last week of a weekly block. */
  endDate: '',
  weekly: false,
  weekdays: [] as number[],
  note: '',
})

const endDate = computed(() => form.endDate || form.date)
const error = computed(() => {
  if (!form.date || !form.startTime || !form.endTime) return t('team.agenda.errors.blockRequired')
  if (form.weekly && form.weekdays.length === 0) return t('team.agenda.errors.blockWeekdays')
  const sameDay = form.weekly || endDate.value === form.date
  if (endDate.value < form.date || (sameDay && form.endTime <= form.startTime)) return t('team.agenda.errors.blockOrder')
  return ''
})

function toggleWeekday(weekday: number): void {
  form.weekdays = form.weekdays.includes(weekday) ? form.weekdays.filter((day) => day !== weekday) : [...form.weekdays, weekday]
}

function submit(): void {
  showErrors.value = true
  if (error.value) return
  emit('add', {
    startsAt: `${form.date} ${form.startTime}`,
    endsAt: `${endDate.value} ${form.endTime}`,
    recurrence: form.weekly ? 'weekly' : null,
    weekdays: form.weekly ? [...form.weekdays].sort() : null,
    note: form.note.trim() || null,
  })
  isFormOpen.value = false
  showErrors.value = false
  form.note = ''
  form.weekdays = []
  form.endDate = ''
}
</script>

<template>
  <SettingsCard :title="t('team.agenda.blocks.title')" :description="t('team.agenda.blocks.description')">
    <p v-if="blocks.length === 0" class="text-sm text-muted">{{ t('team.agenda.blocks.empty') }}</p>
    <ul v-else class="grid gap-2">
      <li v-for="block in blocks" :key="block.id" class="flex items-center gap-3 rounded-card bg-raised px-4 py-3 ring-1 ring-line">
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold">{{ blockLabel(block, t) }}</p>
          <p v-if="block.note" class="truncate text-xs text-muted">{{ block.note }}</p>
        </div>
        <BaseButton variant="ghost" @click="emit('remove', block.id)">{{ t('team.agenda.blocks.unblock') }}</BaseButton>
      </li>
    </ul>

    <BaseButton v-if="!isFormOpen" variant="secondary" @click="isFormOpen = true">{{ t('team.agenda.blocks.add') }}</BaseButton>
    <form v-else class="grid gap-3 rounded-card bg-raised p-4 ring-1 ring-line" novalidate @submit.prevent="submit">
      <ToggleSwitch v-model="form.weekly" :label="t('team.agenda.blocks.weekly')" />
      <div v-if="form.weekly" class="flex flex-wrap gap-2" role="group" :aria-label="t('team.agenda.blocks.weekdays')">
        <button
          v-for="weekday in WEEK_ORDER"
          :key="weekday"
          type="button"
          role="checkbox"
          :aria-checked="form.weekdays.includes(weekday)"
          class="h-9 rounded-button px-3 text-xs font-bold ring-1"
          :class="form.weekdays.includes(weekday) ? 'bg-accent text-on-accent ring-accent' : 'bg-surface ring-line'"
          @click="toggleWeekday(weekday)"
        >
          {{ formatWeekdayLong(weekday) }}
        </button>
      </div>
      <TextField v-model="form.date" type="date" :label="form.weekly ? t('team.agenda.blocks.from') : t('team.agenda.blocks.day')" />
      <div class="grid grid-cols-2 gap-3">
        <TextField v-model="form.startTime" type="time" step="900" :label="t('team.agenda.blocks.startTime')" />
        <TextField v-model="form.endTime" type="time" step="900" :label="t('team.agenda.blocks.endTime')" />
      </div>
      <TextField
        v-model="form.endDate"
        type="date"
        :label="form.weekly ? t('team.agenda.blocks.lastDay') : t('team.agenda.blocks.endDay')"
        :hint="t('team.agenda.blocks.optional')"
      />
      <TextField v-model="form.note" :label="t('team.agenda.blocks.note')" :maxlength="200" />
      <p v-if="showErrors && error" class="text-sm font-semibold text-danger" role="alert">{{ error }}</p>
      <div class="flex gap-2">
        <BaseButton type="submit">{{ t('team.agenda.blocks.save') }}</BaseButton>
        <BaseButton variant="ghost" @click="isFormOpen = false">{{ t('team.cancel') }}</BaseButton>
      </div>
    </form>
  </SettingsCard>
</template>
