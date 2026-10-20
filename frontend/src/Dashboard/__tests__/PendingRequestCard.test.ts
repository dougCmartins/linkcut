import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import PendingRequestCard from '../PendingRequestCard.vue'
import { renderWithPlugins } from '@/utils/testRender'
import { AppointmentStatus } from '@/types/appointmentStatus'

const request = {
  id: 42,
  clientName: 'Lucas Andrade',
  clientWhatsapp: '11988887777',
  serviceId: '1',
  serviceName: 'Corte',
  durationMinutes: 45,
  date: '2026-10-20',
  time: '14:30',
  status: AppointmentStatus.Pending,
}

describe('PendingRequestCard', () => {
  it('shows client, service and time', () => {
    // Arrange & Act
    renderWithPlugins(PendingRequestCard, { props: { request, priceInCents: 5000 } })

    // Assert
    expect(screen.getByText('Lucas Andrade')).toBeTruthy()
    expect(screen.getByText('Corte')).toBeTruthy()
    expect(screen.getByText(/14:30/)).toBeTruthy()
  })

  it('emits accept with the request id', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(PendingRequestCard, { props: { request, priceInCents: 5000 } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Aceitar' }))

    // Assert
    expect(emitted('accept')).toEqual([[42]])
  })

  it('emits reject with the request id', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(PendingRequestCard, { props: { request, priceInCents: 5000 } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Recusar' }))

    // Assert
    expect(emitted('reject')).toEqual([[42]])
  })
})
