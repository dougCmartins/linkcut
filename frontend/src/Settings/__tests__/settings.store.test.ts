import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useSettingsStore } from '../settings.store'
import { SaveResult, SettingsSection } from '../settings.model'
import { ThemePalette } from '@/types/theme'

describe('settings store', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('marks a section as dirty while its draft differs from the saved value', () => {
    // Arrange
    const store = useSettingsStore()

    // Act
    store.drafts.profile.name = 'Diego Souza'

    // Assert
    expect(store.isDirty(SettingsSection.Profile)).toBe(true)
    expect(store.isDirty(SettingsSection.Portfolio)).toBe(false)
    expect(store.profile.name).not.toBe('Diego Souza')
  })

  it('publishes and persists the draft on save', () => {
    // Arrange
    const store = useSettingsStore()
    store.drafts.profile.name = 'Diego Souza'

    // Act
    const result = store.save(SettingsSection.Profile)

    // Assert
    expect(result).toBe(SaveResult.Saved)
    expect(store.profile.name).toBe('Diego Souza')
    expect(store.hasUnsavedChanges).toBe(false)
    setActivePinia(createPinia())
    expect(useSettingsStore().profile.name).toBe('Diego Souza')
  })

  it('refuses to save a section with errors', () => {
    // Arrange
    const store = useSettingsStore()
    store.drafts.profile.whatsappNumber = '123'

    // Act
    const result = store.save(SettingsSection.Profile)

    // Assert
    expect(result).toBe(SaveResult.Invalid)
    expect(store.profile.whatsappNumber).not.toBe('123')
  })

  it('restores the saved value on discard', () => {
    // Arrange
    const store = useSettingsStore()
    const savedPalette = store.theme.palette
    store.drafts.theme.palette = ThemePalette.Ocean

    // Act
    store.discard(SettingsSection.Theme)

    // Assert
    expect(store.drafts.theme.palette).toBe(savedPalette)
    expect(store.isDirty(SettingsSection.Theme)).toBe(false)
  })

  it('falls back to defaults when the stored data is corrupted', () => {
    // Arrange
    localStorage.setItem('linkcut.settings', '{not json')

    // Act
    const store = useSettingsStore()

    // Assert
    expect(store.profile.name).toBe('Rafael Costa')
  })
})
