import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import NewUnitView from '../NewUnitView.vue'
import UnitsCard from '../UnitsCard.vue'
import { useOrganizationStore } from '../organization.store'
import type { OrganizationSummary } from '../organization.api'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { envelope, installFakeApi, type FakeCall } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const INDIVIDUAL: OrganizationSummary = {
  id: 1,
  type: 'individual',
  name: 'Rafa',
  slug: 'rafa',
  units: [{ id: 10, name: 'Rafa', slug: 'rafa', isMain: true }],
}
const TAKEN = 'rafa-centro'

function startSession(role: 'owner' | 'barber'): void {
  useAuthStore().startSession({
    accessToken: 'a',
    refreshToken: 'r',
    accessExpiresAt: Date.now() + 60_000,
    refreshExpiresAt: Date.now() + 120_000,
    user: { id: 7, tenantId: 10, name: role, email: `${role}@example.com`, phoneMasked: null, role, twoFactorChannel: null },
  })
}

function installUnitsApi() {
  let organization = INDIVIDUAL
  return installFakeApi({
    'GET /organization': () => ({
      status: 200,
      body: { ...organization, units: organization.units.map((u) => ({ id: u.id, name: u.name, slug: u.slug, is_main: u.isMain })) },
    }),
    'GET /sign-up/slug': ({ query }: FakeCall) => {
      const slug = query.get('slug') ?? ''
      const taken = slug === TAKEN
      return { status: 200, body: { slug, available: !taken, suggestion: taken ? `${slug}-2` : slug } }
    },
    'POST /organization/units': ({ body }: FakeCall) => {
      if (body?.slug === TAKEN) return envelope('SLUG_UNAVAILABLE', 409)
      const unit = { id: 11, name: String(body?.name), slug: String(body?.slug), isMain: false }
      organization = { ...organization, type: 'multi_unit', units: [...organization.units, unit] }
      return { status: 201, body: { id: 11, name: unit.name, slug: unit.slug, is_main: false } }
    },
    'GET https://viacep.com.br/ws/:cep/json/': () => ({
      status: 200,
      body: { cep: '01310-100', logradouro: 'Avenida Paulista', bairro: 'Bela Vista', localidade: 'São Paulo', uf: 'SP' },
    }),
  })
}

const input = (label: string) => screen.getByLabelText(label) as HTMLInputElement

async function fillAddress(): Promise<void> {
  await fireEvent.update(input('CEP'), '01310-100')
  await vi.waitFor(() => expect(input('Rua').value).toBe('Avenida Paulista'))
  await fireEvent.update(input('Número'), '1578')
}

describe('NewUnitView', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
    vi.useRealTimers()
  })

  it('sends the unit in snake_case and switches the panel to it', async () => {
    // Arrange
    const api = installUnitsApi()
    const selectUnit = vi.fn()
    const { router } = renderWithPlugins(NewUnitView, {}, () => {
      startSession('owner')
      const store = useOrganizationStore()
      store.organization = INDIVIDUAL
      store.selectUnit = selectUnit
    })
    await router.push('/painel/configuracoes')
    await fireEvent.update(input('Nome da filial'), 'Rafa Paulista')
    await screen.findByText('Este link está livre.')
    await fillAddress()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Criar filial' }))

    // Assert
    await vi.waitFor(() => expect(selectUnit).toHaveBeenCalledWith(11))
    const sent = api.calls.find((call) => call.method === 'POST')
    expect(sent?.body).toEqual({
      name: 'Rafa Paulista',
      slug: 'rafa-paulista',
      copy_settings: false,
      address: {
        zip_code: '01310100',
        street: 'Avenida Paulista',
        number: '1578',
        complement: null,
        district: 'Bela Vista',
        city: 'São Paulo',
        state: 'SP',
        latitude: null,
        longitude: null,
      },
    })
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('dashboard'))
    expect(router.currentRoute.value.query.filial).toBe('rafa-paulista')
  })

  it('a taken link shows a free one and does not send', async () => {
    // Arrange
    const api = installUnitsApi()
    renderWithPlugins(NewUnitView, {}, () => {
      startSession('owner')
      useOrganizationStore().organization = INDIVIDUAL
    })
    await fireEvent.update(input('Nome da filial'), 'Rafa Centro')

    // Act
    await screen.findByText('Este link já está em uso.')
    await fillAddress()
    await fireEvent.click(screen.getByRole('button', { name: 'Criar filial' }))

    // Assert
    expect(screen.getByRole('button', { name: `Usar ${TAKEN}-2` })).toBeTruthy()
    expect(api.calls.some((call) => call.method === 'POST')).toBe(false)
  })

  it('a reserved link is refused before asking the API', async () => {
    // Arrange
    const api = installUnitsApi()
    renderWithPlugins(NewUnitView, {}, () => startSession('owner'))

    // Act
    await fireEvent.update(input('Nome da filial'), 'Painel')

    // Assert
    expect(screen.getByText('Este link já está em uso.')).toBeTruthy()
    expect(api.calls.some((call) => call.path.startsWith('/sign-up/slug'))).toBe(false)
  })
})

describe('UnitsCard', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('offers "Adicionar filial" to the owner of an individual account', async () => {
    // Arrange
    installUnitsApi()

    // Act
    renderWithPlugins(UnitsCard, {}, () => startSession('owner'))

    // Assert
    expect(await screen.findByRole('link', { name: 'Adicionar filial' })).toBeTruthy()
    expect(screen.getByText(/sua conta vira uma marca com filiais/)).toBeTruthy()
  })

  it('is not shown to a barber', () => {
    // Arrange
    installUnitsApi()

    // Act
    renderWithPlugins(UnitsCard, {}, () => startSession('barber'))

    // Assert
    expect(screen.queryByText('Adicionar filial')).toBeNull()
    expect(screen.queryByText('Filiais')).toBeNull()
  })
})

describe('organization store', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('reload() brings the new branch and the account becomes multi_unit after the 2nd unit', async () => {
    // Arrange
    installUnitsApi()
    renderWithPlugins(UnitsCard, {}, () => startSession('owner'))
    const store = useOrganizationStore()
    await store.ensureLoaded()
    expect(store.isMultiUnit).toBe(false)

    // Act
    const unit = await store.createUnit({
      name: 'Centro',
      slug: 'rafa-paulista',
      copySettings: true,
      address: { zipCode: '01310100', street: 'Av', number: '1', complement: '', district: 'B', city: 'SP', state: 'SP', latitude: null, longitude: null },
    })

    // Assert
    expect(unit).toEqual({ id: 11, name: 'Centro', slug: 'rafa-paulista', isMain: false })
    expect(store.isMultiUnit).toBe(true)
    expect(store.units.map((u) => u.id)).toEqual([10, 11])
    expect(store.activeUnitId).toBe(10)
  })
})
