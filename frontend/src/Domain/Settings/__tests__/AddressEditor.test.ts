import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import SettingsView from '../SettingsView.vue'
import { installFakeSettingsApi } from './fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

/** ViaCEP answers 01310100 (Av. Paulista) and says any other CEP does not exist. */
const viaCep = {
  'GET https://viacep.com.br/ws/:cep/json/': ({ params }: { params: Record<string, string> }) =>
    params.cep === '01310100'
      ? { status: 200, body: { cep: '01310-100', logradouro: 'Avenida Paulista', bairro: 'Bela Vista', localidade: 'São Paulo', uf: 'SP' } }
      : { status: 200, body: { erro: 'true' } },
}

async function renderLinkTab() {
  const rendered = renderWithPlugins(SettingsView)
  await rendered.router.push('/painel/configuracoes')
  await screen.findByLabelText('CEP')
  return rendered
}

const input = (label: string) => screen.getByLabelText(label) as HTMLInputElement

describe('Address in the Link tab', () => {
  let api: ReturnType<typeof installFakeSettingsApi>

  beforeEach(() => {
    api = installFakeSettingsApi(undefined, viaCep)
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
    Object.defineProperty(navigator, 'geolocation', { value: undefined, configurable: true })
  })

  it('shows the saved address with its point on the map', async () => {
    // Arrange & Act
    await renderLinkTab()

    // Assert
    expect(input('CEP').value).toBe('01304-001')
    expect(input('Rua').value).toBe('Rua Augusta')
    expect(screen.getByText(/Ponto no mapa definido/)).toBeTruthy()
  })

  it('fills the address from the CEP and sends it for the API to find on the map', async () => {
    // Arrange
    await renderLinkTab()

    // Act
    await fireEvent.update(input('CEP'), '01310-100')
    await vi.waitFor(() => expect(input('Rua').value).toBe('Avenida Paulista'))
    await fireEvent.update(input('Número'), '1578')
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    await screen.findByText('Alterações salvas!')
    expect(api.calls.at(-1)?.body?.address).toEqual({
      zip_code: '01310100',
      street: 'Avenida Paulista',
      number: '1578',
      complement: null,
      district: 'Bela Vista',
      city: 'São Paulo',
      state: 'SP',
      latitude: null,
      longitude: null,
    })
    expect(screen.getByText(/Não encontramos este endereço no mapa/)).toBeTruthy()
  })

  it('says when the CEP does not exist', async () => {
    // Arrange
    await renderLinkTab()

    // Act
    await fireEvent.update(input('CEP'), '99999-999')

    // Assert
    expect(await screen.findByText('CEP não encontrado. Confira os números.')).toBeTruthy()
  })

  it('"Estou na barbearia" uses the device location as the point', async () => {
    // Arrange
    const getCurrentPosition = vi.fn((success: PositionCallback) =>
      success({ coords: { latitude: -23.561, longitude: -46.656 } } as GeolocationPosition),
    )
    Object.defineProperty(navigator, 'geolocation', { value: { getCurrentPosition }, configurable: true })
    await renderLinkTab()
    await fireEvent.update(input('Número'), '1510')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: /Estou na barbearia/ }))
    await fireEvent.click(screen.getByRole('button', { name: 'Salvar' }))

    // Assert
    await screen.findByText('Alterações salvas!')
    expect(api.calls.at(-1)?.body?.address).toMatchObject({ number: '1510', latitude: -23.561, longitude: -46.656 })
  })

  it('the address is required', async () => {
    // Arrange
    await renderLinkTab()

    // Act
    await fireEvent.update(input('Rua'), '')

    // Assert
    expect(screen.getAllByText('Campo obrigatório.').length).toBeGreaterThan(0)
    expect((screen.getByRole('button', { name: 'Salvar' }) as HTMLButtonElement).disabled).toBe(true)
  })
})
