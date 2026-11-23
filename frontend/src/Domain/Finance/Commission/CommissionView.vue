<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import BaseTabs from '@/components/ui/BaseTabs.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { useTeamStore } from '@/Domain/Team/team.store'
import CommissionAdjustmentsPanel from './CommissionAdjustmentsPanel.vue'
import CommissionReportPanel from './CommissionReportPanel.vue'
import CommissionRulesPanel from './CommissionRulesPanel.vue'
import CommissionSimulator from './CommissionSimulator.vue'
import { useCommissionStore } from './commission.store'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const auth = useAuthStore()
const team = useTeamStore()
const settings = useSettingsStore()
const commission = useCommissionStore()

const isOwner = computed(() => auth.user?.role === 'owner')
const myStaff = computed(() => team.members.find((member) => member.userId === auth.user?.id) ?? null)
/** The barber only sees his own commission when the owner switched it on; nothing is requested otherwise. */
const canSeeReport = computed(() => isOwner.value || Boolean(myStaff.value?.permissions.seeOwnCommission))

const ready = ref(false)
const error = ref('')
const activeTab = ref(isOwner.value ? 'rules' : 'report')

const tabs = computed(() =>
  isOwner.value
    ? [
        { id: 'rules', label: t('commission.tabs.rules') },
        { id: 'adjustments', label: t('commission.tabs.adjustments') },
        { id: 'report', label: t('commission.tabs.report') },
      ]
    : [{ id: 'report', label: t('commission.tabs.report') }],
)

onMounted(async () => {
  try {
    await team.ensureLoaded()
    if (isOwner.value) await Promise.all([settings.ensureLoaded(), commission.loadConfig()])
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    ready.value = true
  }
})
</script>

<template>
  <div class="mx-auto max-w-lg px-4 py-6">
    <header class="mb-6">
      <RouterLink to="/painel" class="text-sm font-semibold text-accent">← {{ t('commission.back') }}</RouterLink>
      <h1 class="mt-2 text-2xl leading-tight">{{ t('commission.title') }}</h1>
      <p class="mt-1 text-sm text-muted">{{ t('commission.subtitle') }}</p>
    </header>

    <p v-if="error" class="mb-4 rounded-card bg-danger/10 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ error }}</p>

    <p v-if="!ready" class="min-h-40 text-sm text-muted">{{ t('commission.loading') }}</p>
    <p v-else-if="!canSeeReport" class="text-sm text-muted" role="status">{{ t('commission.report.noPermission') }}</p>
    <template v-else>
      <BaseTabs v-if="isOwner" v-model="activeTab" id-prefix="commission" :tabs="tabs" :label="t('commission.tabsLabel')" class="mb-4" />
      <div
        :id="`commission-panel-${activeTab}`"
        role="tabpanel"
        :aria-labelledby="`commission-tab-${activeTab}`"
        class="space-y-4"
      >
        <template v-if="isOwner && activeTab === 'rules'">
          <CommissionRulesPanel />
          <CommissionSimulator />
        </template>
        <template v-else-if="isOwner && activeTab === 'adjustments'">
          <CommissionAdjustmentsPanel />
          <CommissionSimulator />
        </template>
        <CommissionReportPanel v-else :is-owner="isOwner" />
      </div>
    </template>
  </div>
</template>
