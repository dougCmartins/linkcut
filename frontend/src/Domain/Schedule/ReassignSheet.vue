<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import type { StaffMember } from '@/Domain/Team/team.model'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { useAgendaStore } from './agenda.store'
import type { BookingRequest } from './booking.model'

/** "Passar para outro barbeiro" (owner): the active barbers but the current one. A busy barber changes nothing. */
const props = defineProps<{ request: BookingRequest; staff: StaffMember[] }>()
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
const { hasErrorCode, messageKey } = useApiErrors()
const agenda = useAgendaStore()
const dialog = ref<HTMLElement | null>(null)
const busyStaffId = ref<number | null>(null)
const error = ref('')

async function pass(member: StaffMember): Promise<void> {
  busyStaffId.value = member.id
  error.value = ''
  try {
    await agenda.reassign(props.request.id, member.id)
    emit('close')
  } catch (caught) {
    error.value = hasErrorCode(caught, ApiErrorCode.SlotUnavailable)
      ? t('dashboard.reassign.busy', { name: member.name })
      : t(`errors.api.${messageKey(caught)}`)
  } finally {
    busyStaffId.value = null
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
      aria-labelledby="reassign-title"
      tabindex="-1"
      class="relative w-full max-w-md rounded-t-[28px] bg-page p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-float ring-1 ring-line outline-none sm:rounded-[28px]"
    >
      <h2 id="reassign-title" class="text-xl">{{ t('dashboard.reassign.title') }}</h2>
      <p class="mt-1 text-sm text-muted">
        {{ t('dashboard.reassign.subtitle', { client: request.clientName, time: request.time }) }}
      </p>
      <p v-if="error" class="mt-3 rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>
      <ul class="mt-4 grid gap-2">
        <li v-for="member in staff" :key="member.id">
          <BaseButton variant="secondary" block :disabled="busyStaffId !== null" @click="pass(member)">{{ member.name }}</BaseButton>
        </li>
      </ul>
      <BaseButton class="mt-3" variant="ghost" block @click="emit('close')">{{ t('team.cancel') }}</BaseButton>
    </section>
  </div>
</template>
