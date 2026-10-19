import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import TodayAgendaCard from '../TodayAgendaCard.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { AppointmentStatus } from '@/types/appointmentStatus'

const request = {
  id: 'req-42',
  clientName: 'Lucas Andrade',
  clientWhatsapp: '11988887777',
  serviceId: 'haircut',
  date: '2026-10-20',
  time: '14:30',
  status: AppointmentStatus.Confirmed,
}
const service = { id: 'haircut', name: 'Corte', durationMinutes: 45, priceInCents: 5000 }
const completeName = 'Concluir atendimento de Lucas Andrade às 14:30'

describe('TodayAgendaCard', () => {
  it('shows time, client, service and price', () => {
    // Arrange & Act
    renderWithPlugins(TodayAgendaCard, { props: { request, service, isBusy: false } })

    // Assert
    expect(screen.getByText('14:30')).toBeTruthy()
    expect(screen.getByText('Lucas Andrade')).toBeTruthy()
    expect(screen.getByText('Corte')).toBeTruthy()
    expect(screen.getByText(/R\$\s50,00/)).toBeTruthy()
  })

  it('emits complete with the request id', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(TodayAgendaCard, { props: { request, service, isBusy: false } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: completeName }))

    // Assert
    expect(emitted('complete')).toEqual([['req-42']])
  })

  it('disables the button while concluding', () => {
    // Arrange & Act
    renderWithPlugins(TodayAgendaCard, { props: { request, service, isBusy: true } })

    // Assert
    expect((screen.getByRole('button', { name: completeName }) as HTMLButtonElement).disabled).toBe(true)
  })

  it('shows "Concluído" with the charged price instead of the button once concluded', () => {
    // Arrange
    const completed = { ...request, status: AppointmentStatus.Completed, completedPriceInCents: 4500 }

    // Act
    renderWithPlugins(TodayAgendaCard, { props: { request: completed, service, isBusy: false } })

    // Assert
    expect(screen.getByText('Concluído')).toBeTruthy()
    expect(screen.getByText(/R\$\s45,00/)).toBeTruthy()
    expect(screen.queryByRole('button')).toBeNull()
  })
})
