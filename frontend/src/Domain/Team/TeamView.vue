<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { usePageMeta } from '@/composables/usePageMeta'
import { useApiErrors } from '@/composables/useApiErrors'
import UnitPicker from '@/Domain/Tenant/UnitPicker.vue'
import { fetchStaff, inviteStaff, resendInvitation, updateStaff, type StaffMember, type StaffPermissions } from './team.api'

/**
 * "Minha equipe" of the unit picked at the top (docs/decisoes/0001 P3): invite barbers by e-mail, turn each one on or
 * off and set the 3 switches of what they see. Only the owner gets here (the API checks it too).
 */
const { t } = useI18n()
const { messageKey } = useApiErrors()
usePageMeta(() => t('team.metaTitle'), () => t('team.intro'))

const members = ref<StaffMember[]>([])
const status = ref<'loading' | 'ready' | 'failed'>('loading')
const errorMessage = ref('')
const notice = ref('')
const busyId = ref<number | null>(null)
const form = reactive({ name: '', email: '', isSending: false })

const PERMISSION_KEYS: (keyof StaffPermissions)[] = ['seePrices', 'seeOwnCommission', 'seeUnitAgenda']

async function load(): Promise<void> {
  try {
    members.value = await fetchStaff()
    status.value = 'ready'
  } catch {
    status.value = 'failed'
  }
}
onMounted(load)

function showError(caught: unknown): void {
  errorMessage.value = t(`errors.api.${messageKey(caught)}`)
}

async function invite(): Promise<void> {
  if (form.isSending) return
  form.isSending = true
  errorMessage.value = ''
  notice.value = ''
  try {
    const member = await inviteStaff({ name: form.name.trim(), email: form.email.trim() })
    members.value = [...members.value, member]
    notice.value = t('team.invited', { email: member.email })
    form.name = ''
    form.email = ''
  } catch (caught) {
    showError(caught)
  } finally {
    form.isSending = false
  }
}

async function save(member: StaffMember, change: { active?: boolean; permissions?: Partial<StaffPermissions> }): Promise<void> {
  busyId.value = member.id
  errorMessage.value = ''
  try {
    const updated = await updateStaff(member.id, {
      active: change.active ?? member.status !== 'inactive',
      permissions: { ...member.permissions, ...change.permissions },
    })
    members.value = members.value.map((current) => (current.id === updated.id ? updated : current))
  } catch (caught) {
    showError(caught)
  } finally {
    busyId.value = null
  }
}

async function resend(member: StaffMember): Promise<void> {
  busyId.value = member.id
  errorMessage.value = ''
  try {
    await resendInvitation(member.id)
    notice.value = t('team.resent')
  } catch (caught) {
    showError(caught)
  } finally {
    busyId.value = null
  }
}
</script>

<template>
  <main class="mx-auto w-full max-w-2xl px-4 pt-6 pb-16">
    <UnitPicker />
    <header class="flex items-center gap-3">
      <RouterLink
        to="/painel"
        :aria-label="t('team.back')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:-translate-y-0.5"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </RouterLink>
      <div class="min-w-0 flex-1">
        <h1 class="text-2xl leading-tight">{{ t('team.title') }}</h1>
        <p class="text-sm text-muted">{{ t('team.intro') }}</p>
      </div>
    </header>

    <p v-if="notice" class="mt-4 rounded-card bg-success/10 px-3 py-2 text-sm font-semibold text-success ring-1 ring-success/40" role="status">
      {{ notice }}
    </p>
    <p v-if="errorMessage" class="mt-4 rounded-card bg-danger/10 px-3 py-2 text-sm text-danger ring-1 ring-danger/40" role="alert">
      {{ errorMessage }}
    </p>

    <section class="mt-6 rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
      <h2 class="text-lg">{{ t('team.addTitle') }}</h2>
      <form class="mt-3 grid gap-3" @submit.prevent="invite">
        <TextField v-model="form.name" :label="t('team.name')" :maxlength="60" autocomplete="name" />
        <TextField v-model="form.email" :label="t('team.email')" type="email" inputmode="email" :maxlength="120" autocomplete="email" />
        <BaseButton type="submit" variant="primary" block :disabled="form.isSending || !form.name.trim() || !form.email.trim()">
          {{ form.isSending ? t('team.inviting') : t('team.invite') }}
        </BaseButton>
      </form>
    </section>

    <div v-if="status === 'loading'" class="mt-6 grid gap-4" role="status" aria-busy="true">
      <span class="sr-only">{{ t('team.loading') }}</span>
      <div v-for="index in 2" :key="index" class="h-48 animate-pulse rounded-card bg-surface ring-1 ring-line" aria-hidden="true" />
    </div>
    <p v-else-if="status === 'failed'" class="mt-6 text-sm text-danger" role="alert">{{ t('team.loadFailed') }}</p>
    <ul v-else class="mt-6 grid gap-4">
      <li v-for="member in members" :key="member.id" class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="font-bold break-words">
              {{ member.name }} <span v-if="member.isYou" class="text-xs font-semibold text-muted">({{ t('team.you') }})</span>
            </p>
            <p class="text-xs text-muted break-all">{{ member.email }}</p>
          </div>
          <span class="shrink-0 rounded-full bg-raised px-2.5 py-1 text-xs font-semibold">{{ t(`team.status.${member.status}`) }}</span>
        </div>
        <template v-if="!member.isYou">
          <div class="mt-4 grid gap-3">
            <ToggleSwitch
              :model-value="member.status !== 'inactive'"
              :label="t('team.active')"
              :description="t('team.activeHint')"
              @update:model-value="(active: boolean) => save(member, { active })"
            />
            <ToggleSwitch
              v-for="key in PERMISSION_KEYS"
              :key="key"
              :model-value="member.permissions[key]"
              :label="t(`team.permissions.${key}`)"
              @update:model-value="(on: boolean) => save(member, { permissions: { [key]: on } })"
            />
          </div>
          <BaseButton
            v-if="member.status === 'invited'"
            class="mt-3"
            variant="secondary"
            block
            :disabled="busyId === member.id"
            @click="resend(member)"
          >
            {{ t('team.resend') }}
          </BaseButton>
        </template>
      </li>
    </ul>
  </main>
</template>
