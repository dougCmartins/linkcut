import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import ClientAppointmentCard from '@/Domain/Client/ClientAppointmentCard.vue'
import type { ClientAppointment } from '@/Domain/Client/client.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { renderWithPlugins } from '@/utils/testRender'

function appointment(status: AppointmentStatus, hasReview: boolean): ClientAppointment {
  return {
    id: 7,
    serviceName: 'Corte',
    date: '2026-10-12',
    time: '10:00',
    endTime: '10:45',
    status,
    hasReview,
    barbershop: {
      name: 'Rafael Costa',
      slug: 'rafael',
      address: 'Rua Augusta, 1500',
      mapsUrl: '',
      whatsappUrl: '',
      avatarUrl: null,
    },
  }
}

describe('ClientAppointmentCard reviews', () => {
  it('offers Avaliar only on a completed visit that has no review', async () => {
    // Arrange
    const completed = renderWithPlugins(ClientAppointmentCard, {
      props: { appointment: appointment(AppointmentStatus.Completed, false), isUpcoming: false },
    })

    // Assert
    expect(completed.getByRole('link', { name: 'Avaliar' }).getAttribute('href')).toBe('/conta/review/7')

    // Act
    completed.unmount()
    renderWithPlugins(ClientAppointmentCard, {
      props: { appointment: appointment(AppointmentStatus.Completed, true), isUpcoming: false },
    })
    renderWithPlugins(ClientAppointmentCard, {
      props: { appointment: appointment(AppointmentStatus.Confirmed, false), isUpcoming: true },
    })

    // Assert
    expect(screen.queryByRole('link', { name: 'Avaliar' })).toBeNull()
  })
})
