import { createDefaultSettings } from './settings.defaults'
import type { AppSettings, SettingsSection } from './settings.model'
import { deleteImage, getAllImages, putImage } from '@/utils/imageStore'

/**
 * Persistence for the barber settings.
 * Today: localStorage (data) + IndexedDB (images). Later: the backend API — only this file changes.
 */
const STORAGE_KEY = 'linkcut.settings'
const SCHEMA_VERSION = 1

interface StoredSettings {
  version: number
  data: Partial<AppSettings>
}

function readStored(): StoredSettings | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw) as StoredSettings
    return parsed.version === SCHEMA_VERSION && typeof parsed.data === 'object' ? parsed : null
  } catch {
    return null
  }
}

/** Saved settings merged over the defaults, so new fields always have a value. */
export function loadSettings(): AppSettings {
  const defaults = createDefaultSettings()
  const stored = readStored()?.data ?? {}
  return {
    profile: { ...defaults.profile, ...stored.profile },
    portfolio: { ...defaults.portfolio, ...stored.portfolio },
    schedule: {
      ...defaults.schedule,
      ...stored.schedule,
      vacation: { ...defaults.schedule.vacation, ...stored.schedule?.vacation },
    },
    theme: { ...defaults.theme, ...stored.theme },
  }
}

/** Returns false when the browser refuses to store (private mode, quota). */
export function saveSection<K extends SettingsSection>(section: K, value: AppSettings[K]): boolean {
  try {
    const current = readStored()?.data ?? {}
    const next: StoredSettings = { version: SCHEMA_VERSION, data: { ...current, [section]: value } }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(next))
    return true
  } catch {
    return false
  }
}

export function storeImage(id: string, image: Blob): Promise<void> {
  return putImage(id, image)
}

export function loadImages(): Promise<Map<string, Blob>> {
  return getAllImages()
}

export function removeImage(id: string): Promise<void> {
  return deleteImage(id)
}
