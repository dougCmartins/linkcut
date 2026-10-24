import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import AccountDataCard from '../AccountDataCard.vue'
import { SLUG, TENANT } from './fakeSettingsApi'
import * as download from '@/utils/download'
import { envelope, installFakeApi } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const EXPORT = { barbershop: { slug: SLUG }, bookings: [] }

function installAccountApi(deleteHandler = () => ({ status: 204 })) {
  return installFakeApi({
    'GET /tenant': () => ({ status: 200, body: TENANT }),
    'GET /account/export': () => ({ status: 200, body: EXPORT }),
    'DELETE /account': deleteHandler,
  })
}

async function renderCard() {
  const rendered = renderWithPlugins(AccountDataCard)
  await rendered.router.push('/configuracoes')
  await rendered.router.isReady()
  return rendered
}

async function fillDeleteForm(password: string, slug: string): Promise<void> {
  await fireEvent.click(await screen.findByRole('button', { name: 'Excluir minha barbearia' }))
  await fireEvent.update(screen.getByLabelText('Sua senha'), password)
  await fireEvent.update(await screen.findByLabelText(`Digite o link da página para confirmar: ${SLUG}`), slug)
}

describe('AccountDataCard', () => {
  afterEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('downloads every data of the barbershop as JSON', async () => {
    // Arrange
    installAccountApi()
    const save = vi.spyOn(download, 'downloadJson').mockImplementation(() => undefined)
    await renderCard()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Baixar meus dados (JSON)' }))

    // Assert
    await vi.waitFor(() => expect(save).toHaveBeenCalledOnce())
    expect(save.mock.calls[0]?.[1]).toEqual(EXPORT)
  })

  it('deletes the barbershop only after the password and the link typed again', async () => {
    // Arrange
    const { calls } = installAccountApi()
    const { router } = await renderCard()
    await fillDeleteForm('segredo123', 'outro-link')
    const confirm = screen.getByRole('button', { name: 'Excluir definitivamente' }) as HTMLButtonElement
    const disabledWithWrongLink = confirm.disabled

    // Act
    await fireEvent.update(screen.getByLabelText(`Digite o link da página para confirmar: ${SLUG}`), SLUG)
    await fireEvent.click(confirm)

    // Assert
    expect(disabledWithWrongLink).toBe(true)
    await vi.waitFor(() => expect(router.currentRoute.value.path).toBe('/entrar'))
    expect(router.currentRoute.value.query.conta).toBe('excluida')
    expect(calls.at(-1)).toMatchObject({ method: 'DELETE', path: '/account', body: { password: 'segredo123', slug_confirmation: SLUG } })
  })

  it('says when the password is wrong and keeps the barbershop', async () => {
    // Arrange
    installAccountApi(() => envelope('VALIDATION_FAILED', 422, null, { password: ['wrong'] }))
    const { router } = await renderCard()
    await fillDeleteForm('errada', SLUG)

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Excluir definitivamente' }))

    // Assert
    expect(await screen.findByText('Senha incorreta.')).toBeTruthy()
    expect(router.currentRoute.value.path).toBe('/configuracoes')
  })
})
