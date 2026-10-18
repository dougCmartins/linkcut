import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { loadImages, loadSettings, removeImage, saveSection, storeImage } from './settings.api'
import { DEFAULT_THEME } from './settings.defaults'
import {
  SETTINGS_SECTIONS,
  STORED_IMAGE_PREFIX,
  SaveResult,
  isStoredImage,
  storedImageId,
  type AppSettings,
  type ImageSource,
  type SettingsSection,
} from './settings.model'
import { sectionValidators, type ValidationErrors } from './settings.validation'
import { resizeImage } from '@/utils/image'

/** Settings are plain JSON data, so a JSON round-trip is a safe deep copy (works on reactive proxies too). */
function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T
}

function createImageId(): string {
  return typeof crypto !== 'undefined' && 'randomUUID' in crypto
    ? crypto.randomUUID()
    : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`
}

function canCreateObjectUrl(): boolean {
  return typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function'
}

function collectImageSources(settings: AppSettings): ImageSource[] {
  return [
    settings.profile.avatar,
    settings.profile.background,
    settings.portfolio.cover,
    ...settings.portfolio.photos.map((photo) => photo.src),
  ]
}

export const useSettingsStore = defineStore('settings', () => {
  /** What the public pages show. */
  const saved = ref<AppSettings>(loadSettings())
  /** What the barber is editing, one draft per section. */
  const drafts = ref<AppSettings>(clone(saved.value))
  /** Object URLs for uploaded images, by image id. */
  const imageUrls = ref<Record<string, string>>({})

  const profile = computed(() => saved.value.profile)
  const portfolio = computed(() => saved.value.portfolio)
  const schedule = computed(() => saved.value.schedule)
  const theme = computed(() => saved.value.theme)
  const security = computed(() => saved.value.security)

  const errors = computed(() => ({
    profile: sectionValidators.profile(drafts.value.profile),
    portfolio: sectionValidators.portfolio(drafts.value.portfolio),
    schedule: sectionValidators.schedule(drafts.value.schedule),
    theme: sectionValidators.theme(drafts.value.theme),
    security: sectionValidators.security(drafts.value.security),
  }))

  function isDirty(section: SettingsSection): boolean {
    return JSON.stringify(drafts.value[section]) !== JSON.stringify(saved.value[section])
  }

  function sectionErrors(section: SettingsSection): ValidationErrors {
    return errors.value[section]
  }

  const dirtySections = computed(() => SETTINGS_SECTIONS.filter(isDirty))
  const hasUnsavedChanges = computed(() => dirtySections.value.length > 0)

  function save(section: SettingsSection): SaveResult {
    if (Object.keys(sectionErrors(section)).length > 0) return SaveResult.Invalid
    const value = clone(drafts.value[section])
    ;(saved.value as Record<SettingsSection, unknown>)[section] = value
    const isPersisted = saveSection(section, value)
    void removeUnusedImages()
    return isPersisted ? SaveResult.Saved : SaveResult.SessionOnly
  }

  function discard(section: SettingsSection): void {
    ;(drafts.value as Record<SettingsSection, unknown>)[section] = clone(saved.value[section])
    void removeUnusedImages()
  }

  function discardAll(): void {
    SETTINGS_SECTIONS.forEach(discard)
  }

  function resetThemeDraft(): void {
    drafts.value.theme = { ...DEFAULT_THEME }
  }

  /** Turns an image source into something an <img> can show. */
  function resolveImage(source: ImageSource): string {
    if (!source) return ''
    if (isStoredImage(source)) return imageUrls.value[storedImageId(source)] ?? ''
    return source
  }

  function registerImage(id: string, blob: Blob): void {
    if (canCreateObjectUrl()) imageUrls.value[id] = URL.createObjectURL(blob)
  }

  /** Resizes and stores an uploaded file. Returns the source to put in a draft. */
  async function uploadImage(file: Blob): Promise<string> {
    const image = await resizeImage(file)
    const id = createImageId()
    await storeImage(id, image)
    registerImage(id, image)
    return `${STORED_IMAGE_PREFIX}${id}`
  }

  async function loadStoredImages(): Promise<void> {
    const images = await loadImages()
    images.forEach((blob, id) => registerImage(id, blob))
    await removeUnusedImages()
  }

  /** Deletes uploads that neither the saved settings nor the drafts use anymore. */
  async function removeUnusedImages(): Promise<void> {
    const inUse = new Set(
      [...collectImageSources(saved.value), ...collectImageSources(drafts.value)]
        .filter(isStoredImage)
        .map(storedImageId),
    )
    const unused = Object.keys(imageUrls.value).filter((id) => !inUse.has(id))
    await Promise.all(
      unused.map((id) => {
        const url = imageUrls.value[id]
        if (url && typeof URL.revokeObjectURL === 'function') URL.revokeObjectURL(url)
        delete imageUrls.value[id]
        return removeImage(id)
      }),
    )
  }

  return {
    saved,
    drafts,
    profile,
    portfolio,
    schedule,
    theme,
    security,
    errors,
    dirtySections,
    hasUnsavedChanges,
    isDirty,
    sectionErrors,
    save,
    discard,
    discardAll,
    resetThemeDraft,
    resolveImage,
    uploadImage,
    loadStoredImages,
  }
})
