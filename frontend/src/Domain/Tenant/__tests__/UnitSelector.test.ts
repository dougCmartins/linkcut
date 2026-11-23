import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import UnitSelector from '../UnitSelector.vue'
import { useOrganizationStore } from '../organization.store'
import { renderWithPlugins } from '@/utils/testRender'

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
})
