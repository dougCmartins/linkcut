import { beforeEach, describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BookingModal from '../BookingModal.vue'
import { useBookingStore } from '../booking.store'
import { renderWithPlugins } from '@/utils/testRender'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { createDefaultSettings } from '@/Settings/settings.defaults'
import { saveSection } from '@/Settings/settings.api'
import { SettingsSection, type ScheduleSettings } from '@/Settings/settings.model'

function saveSchedule(change: (schedule: ScheduleSettings) => void): void {
  const { schedule } = createDefaultSettings()
  change(schedule)
  saveSection(SettingsSection.Schedule, schedule)
}

beforeEach(() => {
  localStorage.clear()
})

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

describe('BookingModal with the barber settings', () => {
  it('shows the vacation message instead of the form when the agenda is locked', () => {
    // Arrange
    saveSchedule((schedule) => {
      schedule.vacation = { ...schedule.vacation, isEnabled: true, message: 'De volta dia 10!' }
    })

    // Act
    renderWithPlugins(BookingModal)

    // Assert
    expect(screen.getByText('Agenda fechada')).toBeTruthy()
    expect(screen.getByText('De volta dia 10!')).toBeTruthy()
    expect(screen.queryByRole('button', { name: 'Solicitar Agendamento' })).toBeNull()
  })

  it('lists the services configured by the barber', () => {
    // Arrange
    saveSchedule((schedule) => {
      schedule.services = [{ id: 'kids', name: 'Corte infantil', durationMinutes: 30, priceInCents: 3500 }]
    })

    // Act
    renderWithPlugins(BookingModal)

    // Assert
    expect(screen.getByRole('radio', { name: /Corte infantil/ })).toBeTruthy()
    expect(screen.queryByRole('radio', { name: /Barba/ })).toBeNull()
  })

  it('disables the days the barber closed', () => {
    // Arrange
    saveSchedule((schedule) => {
      schedule.weeklyHours = schedule.weeklyHours.map((day) => ({ ...day, isOpen: false }))
    })

    // Act
    renderWithPlugins(BookingModal)

    // Assert
    const days = screen.getAllByRole('radio').filter((element) => element.getAttribute('aria-label'))
    expect(days.length).toBe(7)
    expect(days.every((day) => (day as HTMLButtonElement).disabled)).toBe(true)
  })
})
