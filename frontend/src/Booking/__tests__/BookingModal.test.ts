import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BookingModal from '../BookingModal.vue'
import { useBookingStore } from '../booking.store'
import { renderWithPlugins } from '@/utils/testRender'
import { AppointmentStatus } from '@/types/appointmentStatus'

async function fillValidBooking(): Promise<void> {
  await fireEvent.click(screen.getByRole('radio', { name: /Corte/ }))
  // Pick the first day that has free times (Sundays are closed).
  for (const day of screen.getAllByRole('radio').filter((el) => el.getAttribute('aria-label'))) {
    await fireEvent.click(day)
    const times = screen.queryAllByRole('radio', { name: /^\d{2}:\d{2}$/ })
    if (times[0]) {
      await fireEvent.click(times[0])
      break
    }
  }
  await fireEvent.update(screen.getByLabelText('Nome'), 'João Silva')
  await fireEvent.update(screen.getByLabelText('WhatsApp'), '(11) 91234-5678')
}

describe('BookingModal', () => {
  it('keeps the submit button disabled until the form is complete', async () => {
    // Arrange
    renderWithPlugins(BookingModal)
    const submit = screen.getByRole('button', { name: 'Solicitar Agendamento' })

    // Act
    await fireEvent.click(screen.getByRole('radio', { name: /Corte/ }))

    // Assert
    expect((submit as HTMLButtonElement).disabled).toBe(true)
  })

  it('sends a pending request and shows the confirmation message', async () => {
    // Arrange
    renderWithPlugins(BookingModal)
    const store = useBookingStore()
    const pendingBefore = store.pendingRequests.length
    await fillValidBooking()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))

    // Assert
    expect(screen.getByText('Solicitação enviada!')).toBeTruthy()
    expect(screen.getByText('O barbeiro irá confirmar em instantes.')).toBeTruthy()
    expect(store.pendingRequests).toHaveLength(pendingBefore + 1)
    expect(store.requests.at(-1)?.status).toBe(AppointmentStatus.Pending)
    expect(store.requests.at(-1)?.clientWhatsapp).toBe('11912345678')
  })

  it('emits close when Escape is pressed', async () => {
    // Arrange
    const { emitted } = renderWithPlugins(BookingModal)

    // Act
    await fireEvent.keyDown(window, { key: 'Escape' })

    // Assert
    expect(emitted('close')).toHaveLength(1)
  })
})
