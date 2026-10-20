import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import TodayAgendaCard from '../TodayAgendaCard.vue'
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
  clientDistanceMeters: null,
  status: AppointmentStatus.Confirmed,
}
const completeName = 'Concluir atendimento de Lucas Andrade às 14:30'

describe('TodayAgendaCard', () => {
  it('shows time, client, service and price', () => {
    // Arrange & Act
    renderWithPlugins(TodayAgendaCard, { props: { request, priceInCents: 5000, isBusy: false } })

    // Assert
    expect(screen.getByText('14:30')).toBeTruthy()
    expect(screen.getByText('Lucas Andrade')).toBeTruthy()
    expect(screen.getByText('Corte')).toBeTruthy()
    expect(screen.getByText(/R\$\s50,00/)).toBeTruthy()
  })

  it('emits complete with the request id', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(TodayAgendaCard, { props: { request, priceInCents: 5000, isBusy: false } })

    // Act
    await fireEvent.click(screen.getByRole('button', { name: completeName }))

    // Assert
    expect(emitted('complete')).toEqual([[42]])
  })

  it('disables the button while concluding', () => {
    // Arrange & Act
    renderWithPlugins(TodayAgendaCard, { props: { request, priceInCents: 5000, isBusy: true } })

    // Assert
    expect((screen.getByRole('button', { name: completeName }) as HTMLButtonElement).disabled).toBe(true)
  })

  it('shows "Concluído" with the charged price instead of the button once concluded', () => {
    // Arrange
    const completed = { ...request, status: AppointmentStatus.Completed, completedPriceInCents: 4500 }

    // Act
    renderWithPlugins(TodayAgendaCard, { props: { request: completed, priceInCents: 5000, isBusy: false } })

    // Assert
    expect(screen.getByText('Concluído')).toBeTruthy()
    expect(screen.getByText(/R\$\s45,00/)).toBeTruthy()
    expect(screen.queryByRole('button')).toBeNull()
  })

  it('opens WhatsApp with the reminder ready for the client', () => {
    // Arrange & Act
    renderWithPlugins(TodayAgendaCard, {
      props: { request, priceInCents: 5000, isBusy: false, barbershopName: 'Rafael Costa', barbershopAddress: 'Rua Augusta, 1500' },
    })

    // Assert
    const link = screen.getByRole('link', { name: 'Lembrar Lucas Andrade no WhatsApp' })
    const url = new URL(link.getAttribute('href') ?? '')
    expect(url.origin + url.pathname).toBe('https://wa.me/5511988887777')
    expect(url.searchParams.get('text')).toBe(
      'Oi, Lucas! Passando para lembrar do seu horário hoje às 14:30 na Rafael Costa. Endereço: Rua Augusta, 1500',
    )
  })

  it('has no reminder once concluded', () => {
    // Arrange & Act
    renderWithPlugins(TodayAgendaCard, {
      props: { request: { ...request, status: AppointmentStatus.Completed }, priceInCents: 5000, isBusy: false },
    })

    // Assert
    expect(screen.queryByRole('link', { name: /no WhatsApp/ })).toBeNull()
  })
})
