import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import PendingRequestCard from '../PendingRequestCard.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { AppointmentStatus } from '@/types/appointmentStatus'

const request = {
  id: 'req-42',
  clientName: 'Lucas Andrade',
  clientWhatsapp: '11988887777',
  serviceId: 'haircut',
  date: '2026-10-20',
  time: '14:30',
  status: AppointmentStatus.Pending,
}
const service = { id: 'haircut', name: 'Corte', durationMinutes: 45, priceInCents: 5000 }

describe('PendingRequestCard', () => {
  it('shows client, service and time', () => {
    // Arrange & Act
    renderWithPlugins(PendingRequestCard, { props: { request, service } })

    // Assert
    expect(screen.getByText('Lucas Andrade')).toBeTruthy()
    expect(screen.getByText('Corte')).toBeTruthy()
    expect(screen.getByText(/14:30/)).toBeTruthy()
  })

  it('emits accept with the request id', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(PendingRequestCard, { props: { request, service } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Aceitar' }))

    // Assert
    expect(emitted('accept')).toEqual([['req-42']])
  })

  it('emits reject with the request id', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(PendingRequestCard, { props: { request, service } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Recusar' }))

    // Assert
    expect(emitted('reject')).toEqual([['req-42']])
  })
})
