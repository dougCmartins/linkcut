<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, type Component } from 'vue'
import { useI18n } from 'vue-i18n'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import BaseTabs, { type TabItem } from '@/components/ui/BaseTabs.vue'
import AppearanceSettingsTab from './AppearanceSettingsTab.vue'
import LinkSettingsTab from './LinkSettingsTab.vue'
import PortfolioSettingsTab from './PortfolioSettingsTab.vue'
import ReviewsPanelView from '@/Domain/Review/ReviewsPanelView.vue'
import SaveBar from './SaveBar.vue'
import ScheduleSettingsTab from './ScheduleSettingsTab.vue'
import SecuritySettingsTab from './SecuritySettingsTab.vue'
import { useSettingsStore } from './settings.store'
import { SaveResult, SettingsTab, TAB_SECTIONS } from './settings.model'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { LoadStatus } from '@/types/loadStatus'

const STATUS_VISIBLE_MS = 2600
const TAB_ID_PREFIX = 'settings'

const TAB_COMPONENTS: Record<SettingsTab, Component> = {
  [SettingsTab.Link]: LinkSettingsTab,
  [SettingsTab.Portfolio]: PortfolioSettingsTab,
  [SettingsTab.Reviews]: ReviewsPanelView,
  [SettingsTab.Schedule]: ScheduleSettingsTab,
  [SettingsTab.Appearance]: AppearanceSettingsTab,
  [SettingsTab.Security]: SecuritySettingsTab,
}

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const store = useSettingsStore()
const { messageKey } = useApiErrors()

function isSettingsTab(value: unknown): value is SettingsTab {
  return Object.values(SettingsTab).includes(value as SettingsTab)
}

const activeTab = computed<SettingsTab>({
  get: () => (isSettingsTab(route.query.aba) ? route.query.aba : SettingsTab.Link),
  set: (tab) => void router.replace({ query: { ...route.query, aba: tab } }),
})
const activeSection = computed(() => TAB_SECTIONS[activeTab.value])

const tabs = computed<TabItem[]>(() =>
  Object.values(SettingsTab).map((tab) => ({
    id: tab,
    label: t(`settings.tabs.${tab}`),
    hasBadge: isTabDirty(tab),
  })),
)

function isTabDirty(tab: SettingsTab): boolean {
  const section = TAB_SECTIONS[tab]
  return section ? store.isDirty(section) : false
}

const isActiveDirty = computed(() => isTabDirty(activeTab.value))
const canSave = computed(() =>
  activeSection.value ? Object.keys(store.sectionErrors(activeSection.value)).length === 0 : false,
)

const statusMessage = ref('')
let statusTimer: ReturnType<typeof setTimeout> | undefined

function showStatus(message: string): void {
  statusMessage.value = message
  clearTimeout(statusTimer)
  statusTimer = setTimeout(() => (statusMessage.value = ''), STATUS_VISIBLE_MS)
}

const isSaving = ref(false)

async function save(): Promise<void> {
  if (!activeSection.value || isSaving.value) return
  isSaving.value = true
  try {
    if ((await store.save(activeSection.value)) === SaveResult.Saved) showStatus(t('settings.saved'))
  } catch (caught) {
    showStatus(t(`errors.api.${messageKey(caught)}`))
  } finally {
    isSaving.value = false
  }
}

onBeforeRouteLeave(() => {
  if (!store.hasUnsavedChanges) return true
  if (!window.confirm(t('settings.unsavedConfirm'))) return false
  store.discardAll()
  return true
})

function warnBeforeUnload(event: BeforeUnloadEvent): void {
  if (store.hasUnsavedChanges) event.preventDefault()
}

onMounted(() => {
  window.addEventListener('beforeunload', warnBeforeUnload)
  void store.ensureLoaded()
})
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', warnBeforeUnload)
  clearTimeout(statusTimer)
})
</script>

<template>
  <main class="mx-auto w-full max-w-5xl px-4 pt-6 pb-32">
    <header class="flex items-center gap-3">
      <RouterLink
        to="/painel"
        :aria-label="t('settings.back')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:-translate-y-0.5"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </RouterLink>
      <div class="min-w-0">
        <h1 class="text-2xl leading-tight">{{ t('settings.title') }}</h1>
        <p class="text-sm text-muted">{{ t('settings.subtitle') }}</p>
      </div>
    </header>

    <p v-if="store.status === LoadStatus.Loading" class="mt-10 text-center text-sm text-muted" role="status">
      {{ t('settings.loading') }}
    </p>
    <div v-else-if="store.status === LoadStatus.Failed" class="mt-10 grid justify-items-center gap-4 text-center" role="alert">
      <p class="text-sm text-muted">{{ t('settings.loadFailed') }}</p>
      <BaseButton variant="secondary" @click="store.ensureLoaded()">{{ t('settings.retry') }}</BaseButton>
    </div>

    <template v-if="store.isLoaded">
      <BaseTabs v-model="activeTab" class="mt-6" :tabs="tabs" :id-prefix="TAB_ID_PREFIX" :label="t('settings.tabsLabel')" />

      <section
        :id="`${TAB_ID_PREFIX}-panel-${activeTab}`"
        role="tabpanel"
        :aria-labelledby="`${TAB_ID_PREFIX}-tab-${activeTab}`"
        class="mt-6"
      >
        <component :is="TAB_COMPONENTS[activeTab]" />
      </section>

      <SaveBar
        v-if="isActiveDirty"
        :can-save="canSave"
        :is-saving="isSaving"
        @save="save"
        @discard="activeSection && store.discard(activeSection)"
      />
    </template>

    <p
      aria-live="polite"
      class="pointer-events-none fixed inset-x-0 bottom-24 z-40 mx-auto w-fit max-w-[90vw] rounded-full bg-ink px-5 py-3 text-sm font-semibold text-page shadow-float transition"
      :class="statusMessage ? 'opacity-100' : 'translate-y-2 opacity-0'"
    >
      {{ statusMessage }}
    </p>
  </main>
</template>
