<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useTeamStore } from './team.store'
import type { StaffMember, StaffPermissions } from './team.model'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const auth = useAuthStore()
const team = useTeamStore()

const isOwner = computed(() => auth.user?.role === 'owner')
const error = ref('')
const inviting = ref(false)
const inviteName = ref('')
const inviteEmail = ref('')
const showInviteForm = ref(false)

onMounted(() => {
  void team.ensureLoaded().catch((caught: unknown) => {
    error.value = t(`errors.api.${messageKey(caught)}`)
  })
})

async function submitInvite(): Promise<void> {
  if (!inviteName.value.trim() || !inviteEmail.value.trim()) return
  inviting.value = true
  error.value = ''
  try {
    await team.invite(inviteName.value.trim(), inviteEmail.value.trim())
    inviteName.value = ''
    inviteEmail.value = ''
    showInviteForm.value = false
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    inviting.value = false
  }
}

async function toggleActive(member: StaffMember, active: boolean): Promise<void> {
  error.value = ''
  try {
    await team.setActive(member.id, active)
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

async function togglePermission(member: StaffMember, key: keyof StaffPermissions, value: boolean): Promise<void> {
  error.value = ''
  try {
    await team.setPermissions(member.id, { ...member.permissions, [key]: value })
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}
</script>

<template>
  <div class="mx-auto max-w-lg px-4 py-6">
    <header class="mb-6 flex items-start justify-between gap-3">
      <div>
        <RouterLink to="/painel" class="text-sm font-semibold text-accent">← {{ t('team.back') }}</RouterLink>
        <h1 class="mt-2 text-2xl leading-tight">{{ t('team.title') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ t('team.subtitle') }}</p>
      </div>
    </header>

    <p v-if="error" class="mb-4 rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">
      {{ error }}
    </p>

    <div v-if="isOwner" class="mb-6">
      <BaseButton v-if="!showInviteForm" type="button" @click="showInviteForm = true">
        {{ t('team.addBarber') }}
      </BaseButton>
      <form v-else class="space-y-3 rounded-card bg-surface p-4 ring-1 ring-line" @submit.prevent="submitInvite">
        <TextField v-model="inviteName" :label="t('team.inviteName')" autocomplete="name" required />
        <TextField v-model="inviteEmail" :label="t('team.inviteEmail')" type="email" autocomplete="email" required />
        <div class="flex gap-2">
          <BaseButton type="submit" :disabled="inviting">{{ t('team.sendInvite') }}</BaseButton>
          <BaseButton type="button" variant="ghost" @click="showInviteForm = false">{{ t('team.cancel') }}</BaseButton>
        </div>
      </form>
    </div>

    <ul v-if="team.loaded" class="space-y-4" :aria-label="t('team.listLabel')">
      <li
        v-for="member in team.members"
        :key="member.id"
        class="rounded-card bg-surface p-4 ring-1 ring-line"
      >
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="font-semibold truncate">{{ member.name }}</p>
            <p class="text-xs text-muted">
              {{ member.pendingInvite ? t('team.pendingInvite') : member.active ? t('team.active') : t('team.inactive') }}
            </p>
          </div>
          <ToggleSwitch
            v-if="isOwner && !member.pendingInvite"
            :model-value="member.active"
            :label="t('team.toggleActive', { name: member.name })"
            @update:model-value="toggleActive(member, $event)"
          />
        </div>
        <div v-if="isOwner" class="mt-4 space-y-3 border-t border-line pt-3">
          <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ t('team.permissions') }}</p>
          <ToggleSwitch
            :model-value="member.permissions.seePrices"
            :label="t('team.perm.seePrices')"
            @update:model-value="(v: boolean) => togglePermission(member, 'seePrices', v)"
          />
          <ToggleSwitch
            :model-value="member.permissions.seeOwnCommission"
            :label="t('team.perm.seeOwnCommission')"
            @update:model-value="(v: boolean) => togglePermission(member, 'seeOwnCommission', v)"
          />
          <ToggleSwitch
            :model-value="member.permissions.seeUnitAgenda"
            :label="t('team.perm.seeUnitAgenda')"
            @update:model-value="(v: boolean) => togglePermission(member, 'seeUnitAgenda', v)"
          />
        </div>
      </li>
    </ul>
    <p v-else-if="team.loading" class="text-sm text-muted">{{ t('team.loading') }}</p>
  </div>
</template>
