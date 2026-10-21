import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import SettingsView from '../SettingsView.vue'
import { useSettingsStore } from '../settings.store'
import { createTestSettings, installFakeSettingsApi, testImage } from './fakeSettingsApi'
import { identityRoutes } from '@/Domain/Identity/__tests__/fakeIdentityApi'
import { envelope, type FakeHandler } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'
import { preloadImage } from '@/utils/image'

// The browser never loads images in tests: each test says whether the uploaded image displays.
vi.mock('@/utils/image', async (original) => ({ ...(await original<typeof import('@/utils/image')>()), preloadImage: vi.fn() }))

const SAVED_AVATAR = testImage(1).url

function pngFile(): File {
  return new File([new Uint8Array([137, 80, 78, 71])], 'foto.png', { type: 'image/png' })
}

async function renderLinkTab(uploads: FakeHandler[]) {
  installFakeSettingsApi(createTestSettings(), {
    ...identityRoutes(),
    'POST /settings/images': (call) => uploads.shift()?.(call) ?? envelope('SERVER_ERROR', 500),
  })
  const rendered = renderWithPlugins(SettingsView)
  await rendered.router.push('/painel/configuracoes?aba=link')
  await rendered.router.isReady()
  await screen.findByLabelText('Nome')
  return rendered
}

const avatarSources = () =>
  [...document.querySelectorAll('img')].map((image) => image.getAttribute('src')).filter((src) => src?.includes('cdn.test'))

async function chooseAvatar(): Promise<void> {
  const input = document.querySelector<HTMLInputElement>('input[type="file"]')!
  await fireEvent.change(input, { target: { files: [pngFile()] } })
}

describe('image upload in the settings', () => {
  beforeEach(() => {
    sessionStorage.clear()
    vi.mocked(preloadImage).mockReset().mockResolvedValue(undefined)
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('shows the new image once the API accepted it and it displays', async () => {
    // Arrange
    await renderLinkTab([() => ({ status: 201, body: testImage(50) })])

    // Act
    await chooseAvatar()

    // Assert
    await vi.waitFor(() => expect(avatarSources()).toContain(testImage(50).url))
    expect(useSettingsStore().drafts.profile.avatar?.id).toBe(50)
    expect(screen.queryByRole('alert')).toBeNull()
  })

  it('a refused image puts back the saved one and shows the error under the field', async () => {
    // Arrange: a first upload is accepted (not saved yet), the second one is refused.
    await renderLinkTab([() => ({ status: 201, body: testImage(50) }), () => envelope('VALIDATION_FAILED', 422)])
    await chooseAvatar()
    await vi.waitFor(() => expect(avatarSources()).toContain(testImage(50).url))

    // Act
    await chooseAvatar()

    // Assert
    expect(await screen.findByText('Imagem recusada. Use um arquivo JPG, PNG ou WebP de até 5 MB.')).toBeTruthy()
    expect(avatarSources()).toContain(SAVED_AVATAR)
    expect(avatarSources()).not.toContain(testImage(50).url)
    const store = useSettingsStore()
    expect(store.drafts.profile.avatar).toEqual(store.profile.avatar)
  })

  it('an image that does not display is not kept either', async () => {
    // Arrange
    vi.mocked(preloadImage).mockRejectedValue(new Error('Image did not load'))
    await renderLinkTab([() => ({ status: 201, body: testImage(60) })])

    // Act
    await chooseAvatar()

    // Assert
    expect(await screen.findByText('A imagem foi enviada, mas não conseguimos exibi-la. Tente de novo.')).toBeTruthy()
    expect(avatarSources()).toContain(SAVED_AVATAR)
    expect(useSettingsStore().drafts.profile.avatar?.id).toBe(1)
  })

  it('the error goes away when the next upload works', async () => {
    // Arrange
    await renderLinkTab([() => envelope('VALIDATION_FAILED', 422), () => ({ status: 201, body: testImage(70) })])
    await chooseAvatar()
    await screen.findByText(/Imagem recusada/)

    // Act
    await chooseAvatar()

    // Assert
    await vi.waitFor(() => expect(avatarSources()).toContain(testImage(70).url))
    expect(screen.queryByText(/Imagem recusada/)).toBeNull()
  })
})
