import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchSettings, saveSection, uploadImage as sendImage } from './settings.api'
import { DEFAULT_THEME, createEmptySettings } from './settings.defaults'
import { SETTINGS_SECTIONS, SaveResult, type AppSettings, type ImageRef, type SettingsSection } from './settings.model'
import { sectionValidators, type ValidationErrors } from './settings.validation'
import { LoadStatus } from '@/types/loadStatus'
import { resizeImage } from '@/utils/image'

/** Settings are plain JSON data, so a JSON round-trip is a safe deep copy (works on reactive proxies too). */
function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T
}

/** The barber's own settings (dashboard and editor). Visitors read the public page store instead. */
export const useSettingsStore = defineStore('settings', () => {
  /** As saved on the API. */
  const saved = ref<AppSettings>(createEmptySettings())
  /** What the barber is editing, one draft per section. */
  const drafts = ref<AppSettings>(clone(saved.value))
  const status = ref<LoadStatus>(LoadStatus.Idle)
  let loading: Promise<void> | null = null

  const isLoaded = computed(() => status.value === LoadStatus.Ready)
  const profile = computed(() => saved.value.profile)
  const portfolio = computed(() => saved.value.portfolio)
  const schedule = computed(() => saved.value.schedule)
  const theme = computed(() => saved.value.theme)

  const errors = computed(() => ({
    profile: sectionValidators.profile(drafts.value.profile),
    portfolio: sectionValidators.portfolio(drafts.value.portfolio),
    schedule: sectionValidators.schedule(drafts.value.schedule),
    theme: sectionValidators.theme(drafts.value.theme),
  }))

  function isDirty(section: SettingsSection): boolean {
    return JSON.stringify(drafts.value[section]) !== JSON.stringify(saved.value[section])
  }

  function sectionErrors(section: SettingsSection): ValidationErrors {
    return errors.value[section]
  }

  const dirtySections = computed(() => SETTINGS_SECTIONS.filter(isDirty))
  const hasUnsavedChanges = computed(() => dirtySections.value.length > 0)

  async function load(): Promise<void> {
    status.value = LoadStatus.Loading
    try {
      const settings = await fetchSettings()
      saved.value = settings
      drafts.value = clone(settings)
      status.value = LoadStatus.Ready
    } catch {
      status.value = LoadStatus.Failed
    }
  }

  /** Loads once per session; concurrent callers share the same request. */
  function ensureLoaded(): Promise<void> {
    if (isLoaded.value) return Promise.resolve()
    loading ??= load().finally(() => (loading = null))
    return loading
  }

  /** Forgets everything (logout), so the next barber never sees these settings. */
  function clear(): void {
    saved.value = createEmptySettings()
    drafts.value = clone(saved.value)
    status.value = LoadStatus.Idle
  }

  /** Throws the ApiError when the API refuses or cannot be reached. */
  async function save(section: SettingsSection): Promise<SaveResult> {
    if (Object.keys(sectionErrors(section)).length > 0) return SaveResult.Invalid
    const stored = await saveSection(section, clone(drafts.value[section]))
    ;(saved.value as Record<SettingsSection, unknown>)[section] = stored
    ;(drafts.value as Record<SettingsSection, unknown>)[section] = clone(stored)
    return SaveResult.Saved
  }

  function discard(section: SettingsSection): void {
    ;(drafts.value as Record<SettingsSection, unknown>)[section] = clone(saved.value[section])
  }

  function discardAll(): void {
    SETTINGS_SECTIONS.forEach(discard)
  }

  function resetThemeDraft(): void {
    drafts.value.theme = { ...DEFAULT_THEME }
  }

  /** Shrinks the file in the browser (faster upload) and sends it; the API stores the final WebP. */
  async function uploadImage(file: Blob): Promise<ImageRef> {
    return sendImage(await resizeImage(file))
  }

  return {
    saved,
    drafts,
    status,
    isLoaded,
    profile,
    portfolio,
    schedule,
    theme,
    errors,
    dirtySections,
    hasUnsavedChanges,
    isDirty,
    sectionErrors,
    ensureLoaded,
    clear,
    save,
    discard,
    discardAll,
    resetThemeDraft,
    uploadImage,
  }
})
