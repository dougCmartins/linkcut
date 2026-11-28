<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectField from '@/components/ui/SelectField.vue'
import TextField from '@/components/ui/TextField.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import type { Service } from '@/Domain/Settings/settings.model'
import { reassignBookingRequest } from '@/Domain/Schedule/booking.api'
import type { OrganizationUnit } from '@/Domain/Tenant/organization.api'
import StaffHoursSection from './StaffHoursSection.vue'
import StaffServicesSection from './StaffServicesSection.vue'
import type { StaffMember } from './team.model'
import type { StaffDayHours } from './staffSchedule.model'
import { validateStaffHours } from './staffSchedule.validation'
import {
  blockingAppointments,
  fetchMoveOptions,
  moveStaff,
  type BlockingAppointment,
  type MoveService,
  type StaffMoveKind,
} from './staffMove.api'

/**
 * "Mover para outra filial" (docs/decisoes/0003 P3), in 5 steps: destination and kind → hours there (the destination's
 * hours as a start) → services there (all on) → switches → summary. Upcoming bookings here are listed to be passed to
 * another barber first. The owner decides everything; the app only suggests.
 */
const props = defineProps<{ member: StaffMember; units: OrganizationUnit[]; teammates: StaffMember[] }>()
const emit = defineEmits<{ close: []; moved: [] }>()
const { t } = useI18n()
const { messageKey } = useApiErrors()

const TOTAL_STEPS = 5
const step = ref(1)
const toUnitId = ref(String(props.units[0]?.id ?? ''))
const kind = ref<StaffMoveKind>('transfer')
const endsOn = ref('')
const hours = ref<StaffDayHours[]>([])
const services = ref<MoveService[]>([])
const serviceIds = ref<number[]>([])
const permissions = ref<'keep' | 'reset'>('keep')
const isBusy = ref(false)
const error = ref('')
const blocking = ref<BlockingAppointment[]>([])
const passTo = ref<Record<number, string>>({})
const dialog = ref<HTMLElement | null>(null)

const unitOptions = computed(() => props.units.map((unit) => ({ value: String(unit.id), label: unit.name })))
const unitName = computed(() => props.units.find((unit) => String(unit.id) === toUnitId.value)?.name ?? '')
const otherBarbers = computed(() =>
  props.teammates.filter((mate) => mate.id !== props.member.id && mate.active && !mate.pendingInvite),
)
const hourErrors = computed(() => validateStaffHours(hours.value))
const isClosed = computed(() => hours.value.length > 0 && hours.value.every((day) => !day.isOpen))
/** StaffServicesSection works with the unit's services; the move only needs their names. */
const serviceChoices = computed<Service[]>(() =>
  services.value.map((service) => ({ id: String(service.id), name: service.name, durationMinutes: 0, priceInCents: 0 })),
)
const canContinue = computed(() => {
  if (step.value === 1) return toUnitId.value !== '' && (kind.value === 'transfer' || endsOn.value !== '')
  if (step.value === 2) return Object.keys(hourErrors.value).length === 0 && !isClosed.value
  if (step.value === 3) return serviceIds.value.length > 0
  return true
})

function errorFor(field: string): string | undefined {
  const key = hourErrors.value[field]
  return key ? t(`team.agenda.errors.${key}`) : undefined
}

async function next(): Promise<void> {
  if (!canContinue.value || isBusy.value) return
  error.value = ''
  if (step.value === 1) {
    isBusy.value = true
    try {
      const options = await fetchMoveOptions(props.member.id, Number(toUnitId.value))
      hours.value = options.weeklyHours
      services.value = options.services
      serviceIds.value = options.services.map((service) => service.id)
    } catch (caught) {
      error.value = t(`errors.api.${messageKey(caught)}`)
      return
    } finally {
      isBusy.value = false
    }
  }
  step.value = Math.min(TOTAL_STEPS, step.value + 1)
}

async function confirm(): Promise<void> {
  if (isBusy.value) return
  isBusy.value = true
  error.value = ''
  blocking.value = []
  try {
    await moveStaff(props.member.id, {
      toUnitId: Number(toUnitId.value),
      kind: kind.value,
      endsOn: kind.value === 'loan' ? endsOn.value : null,
      weeklyHours: hours.value,
      serviceIds: serviceIds.value,
      permissions: permissions.value,
    })
    emit('moved')
  } catch (caught) {
    blocking.value = blockingAppointments(caught)
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    isBusy.value = false
  }
}

async function pass(appointment: BlockingAppointment): Promise<void> {
  const staffId = Number(passTo.value[appointment.id] ?? otherBarbers.value[0]?.id)
  if (!staffId) return
  error.value = ''
  try {
    await reassignBookingRequest(appointment.id, staffId)
    blocking.value = blocking.value.filter((item) => item.id !== appointment.id)
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') emit('close')
}
onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  dialog.value?.focus()
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" aria-hidden="true" @click="emit('close')" />
    <section
      ref="dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="move-title"
      tabindex="-1"
      class="relative flex max-h-[92dvh] w-full max-w-md flex-col overflow-y-auto rounded-t-[28px] bg-page p-5 shadow-float ring-1 ring-line outline-none sm:rounded-[28px]"
    >
      <header class="flex items-start justify-between gap-3">
        <div>
          <p class="text-xs font-semibold text-muted">{{ t('staffMove.stepOf', { current: step, total: TOTAL_STEPS }) }}</p>
          <h2 id="move-title" class="text-xl">{{ t('staffMove.title', { name: member.name }) }}</h2>
        </div>
        <button type="button" class="text-sm font-semibold text-muted" @click="emit('close')">{{ t('staffMove.close') }}</button>
      </header>

      <div class="mt-4 grid gap-4">
        <template v-if="step === 1">
          <SelectField v-model="toUnitId" :label="t('staffMove.destination')" :options="unitOptions" />
          <fieldset class="grid gap-2">
            <legend class="pl-2 text-xs font-semibold text-muted">{{ t('staffMove.kindLabel') }}</legend>
            <label class="flex items-start gap-3 rounded-card bg-surface p-3 ring-1 ring-line">
              <input v-model="kind" type="radio" value="transfer" class="mt-1" />
              <span><span class="block font-semibold">{{ t('staffMove.transfer') }}</span><span class="text-xs text-muted">{{ t('staffMove.transferHint') }}</span></span>
            </label>
            <label class="flex items-start gap-3 rounded-card bg-surface p-3 ring-1 ring-line">
              <input v-model="kind" type="radio" value="loan" class="mt-1" />
              <span><span class="block font-semibold">{{ t('staffMove.loan') }}</span><span class="text-xs text-muted">{{ t('staffMove.loanHint') }}</span></span>
            </label>
          </fieldset>
          <TextField v-if="kind === 'loan'" v-model="endsOn" :label="t('staffMove.endsOn')" type="date" />
        </template>

        <template v-else-if="step === 2">
          <p class="text-sm text-muted">{{ t('staffMove.hoursHint') }}</p>
          <StaffHoursSection v-model="hours" :error-for="errorFor" :is-closed="isClosed" />
        </template>

        <StaffServicesSection v-else-if="step === 3" v-model="serviceIds" :services="serviceChoices" />

        <fieldset v-else-if="step === 4" class="grid gap-2">
          <legend class="pl-2 text-xs font-semibold text-muted">{{ t('staffMove.steps.permissions') }}</legend>
          <label class="flex items-center gap-3 rounded-card bg-surface p-3 ring-1 ring-line">
            <input v-model="permissions" type="radio" value="keep" />
            <span class="font-semibold">{{ t('staffMove.permissionsKeep') }}</span>
          </label>
          <label class="flex items-center gap-3 rounded-card bg-surface p-3 ring-1 ring-line">
            <input v-model="permissions" type="radio" value="reset" />
            <span class="font-semibold">{{ t('staffMove.permissionsReset') }}</span>
          </label>
        </fieldset>

        <div v-else class="grid gap-2 rounded-card bg-surface p-4 text-sm ring-1 ring-line">
          <p class="font-semibold">
            {{
              kind === 'loan'
                ? t('staffMove.summaryLoan', { name: member.name, unit: unitName, date: endsOn })
                : t('staffMove.summaryTransfer', { name: member.name, unit: unitName })
            }}
          </p>
          <p class="text-muted">{{ t('staffMove.summaryServices', { count: serviceIds.length }) }}</p>
          <p class="text-muted">{{ permissions === 'keep' ? t('staffMove.permissionsKeep') : t('staffMove.permissionsReset') }}</p>
        </div>

        <p v-if="error" class="rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

        <div v-if="blocking.length" class="grid gap-2">
          <p class="text-sm font-semibold">{{ t('staffMove.blockingTitle') }}</p>
          <ul class="grid gap-2">
            <li v-for="appointment in blocking" :key="appointment.id" class="rounded-card bg-surface p-3 text-sm ring-1 ring-line">
              <p class="font-semibold">{{ appointment.date }} · {{ appointment.startsAt }} · {{ appointment.clientName }}</p>
              <div v-if="otherBarbers.length" class="mt-2 flex items-end gap-2">
                <SelectField
                  :model-value="passTo[appointment.id] ?? String(otherBarbers[0]?.id ?? '')"
                  class="flex-1"
                  :label="t('staffMove.passTo')"
                  :options="otherBarbers.map((mate) => ({ value: String(mate.id), label: mate.name }))"
                  @update:model-value="(value: string) => (passTo[appointment.id] = value)"
                />
                <BaseButton variant="secondary" @click="pass(appointment)">{{ t('staffMove.pass') }}</BaseButton>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <footer class="mt-5 flex gap-2">
        <BaseButton v-if="step > 1" variant="ghost" :disabled="isBusy" @click="step -= 1">{{ t('staffMove.back') }}</BaseButton>
        <BaseButton v-if="step < TOTAL_STEPS" class="flex-1" variant="primary" :disabled="!canContinue || isBusy" @click="next">
          {{ isBusy ? t('staffMove.loading') : t('staffMove.next') }}
        </BaseButton>
        <BaseButton v-else class="flex-1" variant="primary" :disabled="isBusy" @click="confirm">
          {{ isBusy ? t('staffMove.confirming') : blocking.length ? t('staffMove.retry') : t('staffMove.confirm') }}
        </BaseButton>
      </footer>
    </section>
  </div>
</template>
