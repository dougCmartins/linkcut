import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import UnitPicker from '../UnitPicker.vue'
import { restoreActiveUnit } from '../unit.store'
import { http, setActiveUnit } from '@/http/client'
import { envelope, installFakeApi } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const unit = (id: number, name: string, isMain: boolean) => ({ id, name, slug: name.toLowerCase(), is_main: isMain, active: true, onboarded: true })

function installUnitsApi(type: 'individual' | 'multi_unit', units = [unit(1, 'Rafael', true)]) {
  return installFakeApi({
    'GET /organization/units': () => ({
      status: 200,
      body: { organization_id: 7, organization_name: 'Rafael', organization_type: type, can_manage: true, units },
    }),
    'GET /tenant': () => ({ status: 200, body: { id: 1, name: 'Rafael', slug: 'rafael', onboarded: true } }),
  })
}

describe('UnitPicker', () => {
  const reload = vi.fn()

  beforeEach(() => {
    localStorage.clear()
    setActiveUnit(null)
    vi.stubGlobal('location', { ...window.location, reload })
  })

  afterEach(() => {
    reload.mockReset()
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('shows nothing to a barber on their own', async () => {
    // Arrange
    const { calls } = installUnitsApi('individual')

    // Act
    renderWithPlugins(UnitPicker)
    await waitFor(() => expect(calls.some((call) => call.path === '/organization/units')).toBe(true))

    // Assert
    expect(screen.queryByLabelText('Unidade')).toBeNull()
  })

  it('lists the main unit and the branches, and switching reloads the dashboard on the picked unit', async () => {
    // Arrange
    installUnitsApi('multi_unit', [unit(1, 'Rafael', true), unit(2, 'Paulista', false)])
    renderWithPlugins(UnitPicker)
    const select = await screen.findByLabelText('Unidade')

    // Act
    await fireEvent.update(select, '2')

    // Assert
    expect(screen.getByRole('option', { name: 'Rafael (matriz)' })).toBeTruthy()
    expect(localStorage.getItem('linkcut.active-unit')).toBe('2')
    expect(reload).toHaveBeenCalledTimes(1)
  })

  it("sends the picked unit on the barber's calls (X-Unit), never on public or client calls", async () => {
    // Arrange
    localStorage.setItem('linkcut.active-unit', '2')
    restoreActiveUnit()
    const { calls } = installUnitsApi('multi_unit')

    // Act
    await http('/tenant')
    await http('/tenant', { auth: false })
    await http('/tenant', { auth: 'client' })

    // Assert
    expect(calls.map((call) => call.headers['X-Unit'])).toEqual(['2', undefined, undefined])
  })

  it('a saved unit that is no longer open to this account goes back to the own unit', async () => {
    // Arrange
    localStorage.setItem('linkcut.active-unit', '9')
    restoreActiveUnit()
    installFakeApi({ 'GET /organization/units': () => envelope('UNIT_FORBIDDEN', 403) })

    // Act
    renderWithPlugins(UnitPicker)

    // Assert
    await waitFor(() => expect(reload).toHaveBeenCalledTimes(1))
    expect(localStorage.getItem('linkcut.active-unit')).toBeNull()
  })
})
