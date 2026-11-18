import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import BioLinkView from '../BioLinkView.vue'
import { createTestSettings, installFakeSettingsApi, publicPageBody, scheduleBody } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

/** The brand link with the branch picker (docs/decisoes/0001 P2). */
const address = (street: string, district: string) => ({
  zip_code: '01310100',
  street,
  number: '900',
  complement: null,
  district,
  city: 'São Paulo',
  state: 'SP',
  latitude: null,
  longitude: null,
})

const brandBody = {
  slug: 'rafael',
  name: 'Rafael',
  units: [
    { slug: 'rafael', name: 'Rafael Costa', is_main: true, address: address('Rua Augusta', 'Consolação'), avatar_url: null },
    { slug: 'rafael-paulista', name: 'Rafael Paulista', is_main: false, address: address('Avenida Paulista', 'Bela Vista'), avatar_url: null },
  ],
}

function installBrandApi(isBrand = true) {
  const branch = createTestSettings()
  branch.profile.name = 'Rafael Paulista'
  branch.profile.bio = 'A filial da Paulista.'
  const main = createTestSettings()
  return installFakeSettingsApi(main, {
    'GET /public/rafael': () => ({ status: 200, body: { ...publicPageBody(main), brand_slug: isBrand ? 'rafael' : null } }),
    'GET /public/brand/rafael': () => ({ status: 200, body: brandBody }),
    'GET /public/rafael-paulista': () => ({ status: 200, body: { ...publicPageBody(branch), brand_slug: null } }),
    'GET /public/rafael-paulista/booking-options': () => ({ status: 200, body: scheduleBody(branch.schedule, false) }),
  })
}

async function renderBrandLink(path = '/rafael') {
  const rendered = renderWithPlugins(BioLinkView)
  await rendered.router.push(path)
  return rendered
}

describe('brand link', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('picking a branch shows its page in place, and "Agendar" opens on that branch', async () => {
    // Arrange
    const reload = vi.fn()
    vi.stubGlobal('location', { ...window.location, reload })
    installBrandApi()
    const { router } = await renderBrandLink()
    const picker = await screen.findByLabelText('Unidade')

    // Act
    await fireEvent.update(picker, 'rafael-paulista')

    // Assert
    expect(await screen.findByRole('heading', { name: 'Rafael Paulista' })).toBeTruthy()
    expect(screen.getByText('A filial da Paulista.')).toBeTruthy()
    expect(router.currentRoute.value.fullPath).toBe('/rafael?unidade=rafael-paulista')
    expect(screen.getByRole('link', { name: /Agendar Horário Agora/ }).getAttribute('href')).toBe('/rafael/agendar?unidade=rafael-paulista')
    expect(screen.getByText(/Avenida Paulista, 900/)).toBeTruthy()
    expect(reload).not.toHaveBeenCalled()
  })

  it('a barber on their own has no picker and the brand is never asked for', async () => {
    // Arrange
    const { calls } = installBrandApi(false)

    // Act
    await renderBrandLink()
    await screen.findByRole('heading', { name: 'Rafael Costa' })

    // Assert
    expect(screen.queryByLabelText('Unidade')).toBeNull()
    expect(calls.some((call) => call.path.startsWith('/public/brand/'))).toBe(false)
  })

  it("a branch's own link stays its own page, without the picker", async () => {
    // Arrange
    installBrandApi()

    // Act
    await renderBrandLink('/rafael-paulista')

    // Assert
    expect(await screen.findByRole('heading', { name: 'Rafael Paulista' })).toBeTruthy()
    expect(screen.queryByLabelText('Unidade')).toBeNull()
  })

  it('a picked slug that is not a unit of the brand is ignored', async () => {
    // Arrange
    const { calls } = installBrandApi()

    // Act
    await renderBrandLink('/rafael?unidade=outra-barbearia')
    await screen.findByLabelText('Unidade')

    // Assert
    await waitFor(() => expect(screen.getByRole('heading', { name: 'Rafael Costa' })).toBeTruthy())
    expect(calls.some((call) => call.path === '/public/outra-barbearia')).toBe(false)
  })
})
