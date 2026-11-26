<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, onBeforeRouteLeave, useRoute } from 'vue-router'
import { useApiErrors } from '@/composables/useApiErrors'
import SaveBar from '@/Domain/Settings/SaveBar.vue'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import StaffBlocksSection from './StaffBlocksSection.vue'
import StaffHoursSection from './StaffHoursSection.vue'
import StaffServicesSection from './StaffServicesSection.vue'
import { useStaffScheduleStore } from './staffSchedule.store'
import { validateStaffHours } from './staffSchedule.validation'
import type { NewStaffTimeBlock } from './staffSchedule.model'
import { useTeamStore } from './team.store'

/**
 * A barber's agenda, set by the owner (the route guard sends a barber back to the panel): working hours with the
 * break, the services they do and the blocks. Hours and services share one "Salvar"; blocks save on their own.
 */
const { t } = useI18n()
const route = useRoute()
const { messageKey } = useApiErrors()
const schedule = useStaffScheduleStore()
const team = useTeamStore()
const settings = useSettingsStore()

const staffId = computed(() => Number(route.params.id))
const member = computed(() => team.members.find((staff) => staff.id === staffId.value) ?? null)
const isLoaded = ref(false)
const isSaving = ref(false)
const error = ref('')

const hourErrors = computed(() => validateStaffHours(schedule.hours))
const canSave = computed(() => Object.keys(hourErrors.value).length === 0 && !schedule.hasNoService)

function errorFor(field: string): string | undefined {
  const key = hourErrors.value[field]
  return key ? t(`team.agenda.errors.${key}`) : undefined
}

async function run(action: () => Promise<void>): Promise<void> {
  error.value = ''
  try {
    await action()
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

async function save(): Promise<void> {
  if (!canSave.value || isSaving.value) return
  isSaving.value = true
  await run(() => schedule.save())
  isSaving.value = false
}

const addBlock = (block: NewStaffTimeBlock) => run(() => schedule.addBlock(block))
const removeBlock = (blockId: number) => run(() => schedule.removeBlock(blockId))

onBeforeRouteLeave(() => {
  if (!schedule.isDirty) return true
  if (!window.confirm(t('settings.unsavedConfirm'))) return false
  schedule.discard()
  return true
})

onMounted(() => void team.ensureLoaded())

// Loads (again) when the barber in the address changes.
watch(
  staffId,
  async (id) => {
    if (!Number.isInteger(id)) return
    isLoaded.value = false
    await run(async () => {
      await Promise.all([schedule.load(id), settings.ensureLoaded()])
      isLoaded.value = true
    })
  },
  { immediate: true },
)
</script>

<template>
  <main class="mx-auto w-full max-w-lg px-4 pt-6 pb-32">
    <RouterLink :to="{ name: 'team' }" class="text-sm font-semibold text-accent">← {{ t('team.agenda.back') }}</RouterLink>
    <h1 class="mt-2 text-2xl leading-tight">{{ t('team.agenda.title', { name: member?.name ?? '' }) }}</h1>
    <p class="mt-1 text-sm text-muted">{{ t('team.agenda.subtitle') }}</p>

    <p v-if="error" class="mt-4 rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>
    <p v-if="!isLoaded && !error" class="mt-10 text-center text-sm text-muted" role="status">{{ t('team.loading') }}</p>

    <div v-if="isLoaded" class="mt-6 grid gap-5">
      <StaffHoursSection v-model="schedule.hours" :error-for="errorFor" :is-closed="schedule.isAgendaClosed" />
      <StaffServicesSection v-model="schedule.serviceIds" :services="settings.schedule.services" />
      <StaffBlocksSection :blocks="schedule.blocks" @add="addBlock" @remove="removeBlock" />
    </div>

    <SaveBar v-if="schedule.isDirty" :can-save="canSave" :is-saving="isSaving" @save="save" @discard="schedule.discard()" />
  </main>
</template>
