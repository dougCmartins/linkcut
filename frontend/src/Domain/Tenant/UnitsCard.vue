<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import SettingsCard from '@/Domain/Settings/SettingsCard.vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useOrganizationStore } from './organization.store'

/** "Filiais" in Settings: the brand's units and the way to add one. Only the owner sees it. */
const { t } = useI18n()
const auth = useAuthStore()
const organization = useOrganizationStore()

const isOwner = computed(() => auth.user?.role === 'owner')

onMounted(() => {
  if (isOwner.value) void organization.ensureLoaded()
})
</script>

<template>
  <SettingsCard
    v-if="isOwner"
    :title="t('organization.unitsCard.title')"
    :description="organization.isMultiUnit ? t('organization.unitsCard.multiDescription') : t('organization.unitsCard.individualDescription')"
  >
    <ul v-if="organization.isMultiUnit" class="grid gap-2">
      <li v-for="unit in organization.units" :key="unit.id" class="flex items-center justify-between gap-3 text-sm">
        <span class="font-semibold">{{ unit.isMain ? t('organization.mainUnit', { name: unit.name }) : unit.name }}</span>
        <span class="truncate text-muted">/{{ unit.slug }}</span>
      </li>
    </ul>
    <BaseButton variant="secondary" :to="'/painel/unidades/nova'">{{ t('organization.unitsCard.add') }}</BaseButton>
  </SettingsCard>
</template>
