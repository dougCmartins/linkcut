import { describe, expect, it, vi } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import UnitSelector from '../UnitSelector.vue'
import { useOrganizationStore } from '../organization.store'
import { renderWithPlugins } from '@/utils/testRender'
import { useAuthStore } from '@/Domain/Identity/auth.store'

describe('UnitSelector', () => {
  it('is hidden for individual organizations', async () => {
    // Arrange
    renderWithPlugins(UnitSelector, {}, () => {
      const store = useOrganizationStore()
      store.organization = {
        id: 1,
        type: 'individual',
        name: 'Rafa',
        slug: 'rafa',
        units: [{ id: 10, name: 'Rafa', slug: 'rafa', isMain: true }],
      }
      store.activeUnitId = 10
    })

    // Assert
    expect(screen.queryByLabelText('Unidade')).toBeNull()
  })

  it('shows units for multi_unit and switches the active unit', async () => {
    // Arrange
    const user = userEvent.setup()
    const selectUnit = vi.fn()
    renderWithPlugins(UnitSelector, {}, () => {
      const store = useOrganizationStore()
      store.organization = {
        id: 1,
        type: 'multi_unit',
        name: 'Marca',
        slug: 'marca',
        units: [
          { id: 10, name: 'Matriz', slug: 'marca', isMain: true },
          { id: 11, name: 'Centro', slug: 'marca-centro', isMain: false },
        ],
      }
      store.activeUnitId = 10
      store.selectUnit = selectUnit
    })

    // Act
    await user.selectOptions(screen.getByLabelText('Unidade'), '11')

    // Assert
    expect(selectUnit).toHaveBeenCalledWith(11)
  })

  describe('"Todas as unidades"', () => {
    const multiUnit = {
      id: 1,
      type: 'multi_unit' as const,
      name: 'Marca',
      slug: 'marca',
      units: [
        { id: 10, name: 'Matriz', slug: 'marca', isMain: true },
        { id: 11, name: 'Centro', slug: 'marca-centro', isMain: false },
      ],
    }

    function startSession(role: 'owner' | 'barber'): void {
      useAuthStore().startSession({
        accessToken: 'a',
        refreshToken: 'r',
        accessExpiresAt: Date.now() + 60_000,
        refreshExpiresAt: Date.now() + 120_000,
        user: {
          id: 7,
          tenantId: 10,
          name: role,
          email: `${role}@example.com`,
          phoneMasked: null,
          role,
          twoFactorChannel: null,
          availableChannels: [],
        },
      })
    }

    function renderAs(role: 'owner' | 'barber', type: 'multi_unit' | 'individual' = 'multi_unit') {
      return renderWithPlugins(UnitSelector, {}, () => {
        startSession(role)
        const store = useOrganizationStore()
        store.organization = { ...multiUnit, type }
        store.activeUnitId = 10
      })
    }

    it('is offered to the owner on a report screen and opens the brand report', async () => {
      // Arrange
      const user = userEvent.setup()
      const { router } = renderAs('owner')
      await router.push('/painel/historico')

      // Act
      await user.selectOptions(screen.getByLabelText('Unidade'), 'all')

      // Assert
      expect(screen.getByRole('option', { name: 'Todas as unidades' })).toBeTruthy()
      await waitFor(() => expect(router.currentRoute.value.name).toBe('brand-report'))
    })

    it('shows the option selected on the brand report and leaves it when a unit is chosen', async () => {
      // Arrange
      const user = userEvent.setup()
      const selectUnit = vi.fn()
      const { router } = renderWithPlugins(UnitSelector, {}, () => {
        startSession('owner')
        const store = useOrganizationStore()
        store.organization = multiUnit
        store.activeUnitId = 10
        store.selectUnit = selectUnit
      })
      await router.push('/painel/marca')

      // Act
      expect((screen.getByLabelText('Unidade') as HTMLSelectElement).value).toBe('all')
      await user.selectOptions(screen.getByLabelText('Unidade'), '11')

      // Assert
      expect(selectUnit).toHaveBeenCalledWith(11)
      await waitFor(() => expect(router.currentRoute.value.name).toBe('history'))
    })

    it('is not offered on the screens that are not reports', async () => {
      // Arrange
      const { router } = renderAs('owner')
      await router.push('/painel/equipe')

      // Assert
      expect(screen.getByLabelText('Unidade')).toBeTruthy()
      expect(screen.queryByRole('option', { name: 'Todas as unidades' })).toBeNull()
    })

    it('is never offered to a barber', async () => {
      // Arrange
      const { router } = renderAs('barber')
      await router.push('/painel/historico')

      // Assert
      expect(screen.queryByRole('option', { name: 'Todas as unidades' })).toBeNull()
    })

    it('is never offered to an individual organization', async () => {
      // Arrange
      const { router } = renderAs('owner', 'individual')
      await router.push('/painel/historico')

      // Assert
      expect(screen.queryByLabelText('Unidade')).toBeNull()
      expect(screen.queryByRole('option', { name: 'Todas as unidades' })).toBeNull()
    })
  })
})
