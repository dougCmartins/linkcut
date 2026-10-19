import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useSettingsStore } from '../settings.store'
import { SaveResult, SettingsSection } from '../settings.model'
import { createTestSettings, installFakeSettingsApi, testImage } from './fakeSettingsApi'
import { LoadStatus } from '@/types/loadStatus'
import { ThemePalette } from '@/types/theme'
import { envelope, installFakeApi } from '@/utils/testFetch'

describe('settings store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  async function loadedStore() {
    const api = installFakeSettingsApi()
    const store = useSettingsStore()
    await store.ensureLoaded()
    return { store, api }
  }

  it('loads the settings from the API once, including the private service cost', async () => {
    // Arrange
    const api = installFakeSettingsApi()
    const store = useSettingsStore()

    // Act
    await Promise.all([store.ensureLoaded(), store.ensureLoaded()])
    await store.ensureLoaded()

    // Assert
    expect(store.status).toBe(LoadStatus.Ready)
    expect(store.profile.avatar).toEqual(testImage(1))
    expect(store.schedule.services[0]).toEqual({ id: '1', name: 'Corte', durationMinutes: 45, priceInCents: 5000, costInCents: 600 })
    expect(store.drafts).toEqual(store.saved)
    expect(api.calls.filter((call) => call.path === '/settings')).toHaveLength(1)
  })

  it('reports a failed load so the screen can offer a retry', async () => {
    // Arrange
    installFakeApi({ 'GET /settings': () => envelope('SERVER_ERROR', 500) })
    const store = useSettingsStore()

    // Act
    await store.ensureLoaded()

    // Assert
    expect(store.status).toBe(LoadStatus.Failed)
    expect(store.isLoaded).toBe(false)
  })

  it('marks a section as dirty while its draft differs from the saved value', async () => {
    // Arrange
    const { store } = await loadedStore()

    // Act
    store.drafts.profile.name = 'Diego Souza'

    // Assert
    expect(store.isDirty(SettingsSection.Profile)).toBe(true)
    expect(store.isDirty(SettingsSection.Portfolio)).toBe(false)
    expect(store.profile.name).toBe('Rafael Costa')
  })

  it('sends the section in the API format and publishes what the API stored', async () => {
    // Arrange
    const { store, api } = await loadedStore()
    store.drafts.profile.name = 'Diego Souza'
    store.drafts.profile.background = null

    // Act
    const result = await store.save(SettingsSection.Profile)

    // Assert
    expect(result).toBe(SaveResult.Saved)
    expect(api.calls.at(-1)).toMatchObject({
      method: 'PUT',
      path: '/settings/profile',
      body: expect.objectContaining({
        name: 'Diego Souza',
        avatar_image_id: 1,
        background_image_id: null,
        socials: [
          { network: 'instagram', url: 'https://instagram.com/rafa' },
          { network: 'tiktok', url: null },
          { network: 'youtube', url: null },
        ],
      }),
    })
    expect(store.profile.name).toBe('Diego Souza')
    expect(store.hasUnsavedChanges).toBe(false)
  })

  it('gets the real id of a new service back from the API', async () => {
    // Arrange
    const { store, api } = await loadedStore()
    store.drafts.schedule.services.push({ id: 'new-1', name: 'Sobrancelha', durationMinutes: 15, priceInCents: 2000 })

    // Act
    await store.save(SettingsSection.Schedule)

    // Assert
    const sent = api.calls.at(-1)?.body?.services as { id: number | null }[]
    expect(sent.map((service) => service.id)).toEqual([1, 2, null])
    expect(store.schedule.services.map((service) => service.id)).toEqual(['1', '2', '100'])
    expect(store.isDirty(SettingsSection.Schedule)).toBe(false)
  })

  it('refuses to save a section with errors without calling the API', async () => {
    // Arrange
    const { store, api } = await loadedStore()
    const callsBefore = api.calls.length
    store.drafts.profile.whatsappNumber = '123'

    // Act
    const result = await store.save(SettingsSection.Profile)

    // Assert
    expect(result).toBe(SaveResult.Invalid)
    expect(api.calls).toHaveLength(callsBefore)
  })

  it('keeps the draft when the API refuses the save', async () => {
    // Arrange
    const { store } = await loadedStore()
    installFakeApi({ 'PUT /settings/theme': () => envelope('IMAGE_NOT_FOUND', 422) })
    store.drafts.theme.palette = ThemePalette.Ocean

    // Act
    const saving = store.save(SettingsSection.Theme)

    // Assert
    await expect(saving).rejects.toMatchObject({ code: 'IMAGE_NOT_FOUND' })
    expect(store.theme.palette).toBe(ThemePalette.Default)
    expect(store.drafts.theme.palette).toBe(ThemePalette.Ocean)
  })

  it('restores the saved value on discard', async () => {
    // Arrange
    const { store } = await loadedStore()
    store.drafts.theme.palette = ThemePalette.Ocean

    // Act
    store.discard(SettingsSection.Theme)

    // Assert
    expect(store.drafts.theme.palette).toBe(createTestSettings().theme.palette)
    expect(store.isDirty(SettingsSection.Theme)).toBe(false)
  })

  it('uploads an image as multipart and returns its id and url', async () => {
    // Arrange
    const { store, api } = await loadedStore()
    const file = new File(['png'], 'foto.png', { type: 'image/png' })

    // Act
    const image = await store.uploadImage(file)

    // Assert
    expect(image).toEqual(testImage(100))
    expect(api.calls.at(-1)?.form?.get('file')).toBeInstanceOf(File)
  })

  it('forgets the settings on logout', async () => {
    // Arrange
    const { store } = await loadedStore()

    // Act
    store.clear()

    // Assert
    expect(store.status).toBe(LoadStatus.Idle)
    expect(store.profile.name).toBe('')
  })
})
