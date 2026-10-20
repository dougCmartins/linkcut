import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BookingModal from '../BookingModal.vue'
import { SLUG, TODAY, installFakeScheduleApi, requestRow, type ScheduleOptions } from './fakeScheduleApi'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import type { ScheduleSettings } from '@/Domain/Settings/settings.model'
import { createTestSettings } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

/** Renders the modal on the public page of SLUG, with its booking options changed by `change`. */
function renderModal(change: (schedule: ScheduleSettings) => void = () => {}, api: ScheduleOptions = {}) {
  const fake = installFakeScheduleApi(api)
  const rendered = renderWithPlugins(BookingModal, {}, () => {
    const { schedule } = createTestSettings()
    schedule.services = schedule.services.map(({ costInCents: _cost, ...service }) => service)
    change(schedule)
    const store = usePublicPageStore()
    store.slug = SLUG
    store.bookingOptions = schedule
  })
  return { ...rendered, ...fake }
}

const dayButtons = () => screen.getAllByRole('radio').filter((element) => element.getAttribute('aria-label'))

/** The 7 days of the window, once the free times arrived from the API. */
const loadedDays = () =>
  vi.waitFor(() => {
    const days = dayButtons()
    expect(days).toHaveLength(7)
    return days
  })

async function fillValidBooking(): Promise<void> {
  await fireEvent.click(screen.getByRole('radio', { name: /Corte/ }))
  // Tomorrow (Tuesday): today's times may have passed.
  await fireEvent.click((await loadedDays())[1]!)
  await fireEvent.click(await screen.findByRole('radio', { name: '11:00' }))
  await fireEvent.update(screen.getByLabelText('Nome'), 'João Silva')
  await fireEvent.update(screen.getByLabelText('WhatsApp'), '(11) 91234-5678')
}

describe('BookingModal', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('keeps the submit button disabled until the form is complete', async () => {
    // Arrange
    renderModal()
    const submit = screen.getByRole('button', { name: 'Solicitar Agendamento' })

    // Act
    await fireEvent.click(screen.getByRole('radio', { name: /Corte/ }))

    // Assert
    expect((submit as HTMLButtonElement).disabled).toBe(true)
  })

  it('asks the API for the free times of the chosen service', async () => {
    // Arrange
    const { calls } = renderModal()

    // Act
    await fireEvent.click(screen.getByRole('radio', { name: /Corte/ }))

    // Assert
    await vi.waitFor(() =>
      expect(calls.map((call) => call.path)).toEqual([`/public/${SLUG}/availability`, `/public/${SLUG}/availability?service_id=1`]),
    )
    expect(calls.every((call) => !call.headers.Authorization)).toBe(true)
  })

  it('sends the request to the API and shows the confirmation', async () => {
    // Arrange
    const { calls, requests } = renderModal()
    await fillValidBooking()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))

    // Assert
    expect(await screen.findByText('Solicitação enviada!')).toBeTruthy()
    expect(calls.at(-1)?.body).toEqual({
      client_name: 'João Silva',
      client_whatsapp: '11912345678',
      service_id: 1,
      date: '2026-10-20',
      time: '11:00',
    })
    expect(requests.at(-1)?.status).toBe('pending')
  })

  it('when the time was just taken, says so and shows the times still free', async () => {
    // Arrange
    const { requests } = renderModal()
    await fillValidBooking()
    requests.push(requestRow({ id: 50, date: '2026-10-20', starts_at: '11:00' }))

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))

    // Assert
    expect(await screen.findByText('Este horário acabou de ser ocupado. Escolha outro.')).toBeTruthy()
    await vi.waitFor(() => expect(screen.queryByRole('radio', { name: '11:00' })).toBeNull())
    expect(screen.queryByText('Solicitação enviada!')).toBeNull()
  })

  it('emits close when Escape is pressed', async () => {
    // Arrange
    const { emitted } = renderModal()

    // Act
    await fireEvent.keyDown(window, { key: 'Escape' })

    // Assert
    expect(emitted('close')).toHaveLength(1)
  })
})

describe('BookingModal with the booking options of the barbershop', () => {
  it('shows the vacation message instead of the form when the agenda is locked', async () => {
    // Arrange & Act
    renderModal((schedule) => {
      schedule.vacation = { ...schedule.vacation, isEnabled: true, message: 'De volta dia 10!' }
    })

    // Assert
    expect(await screen.findByText('Agenda fechada')).toBeTruthy()
    expect(screen.getByText('De volta dia 10!')).toBeTruthy()
    expect(screen.queryByRole('button', { name: 'Solicitar Agendamento' })).toBeNull()
  })

  it('lists the services configured by the barber', () => {
    // Arrange & Act
    renderModal((schedule) => {
      schedule.services = [{ id: '9', name: 'Corte infantil', durationMinutes: 30, priceInCents: 3500 }]
    })

    // Assert
    expect(screen.getByRole('radio', { name: /Corte infantil/ })).toBeTruthy()
    expect(screen.queryByRole('radio', { name: /Barba/ })).toBeNull()
  })

  it('disables the days without free times', async () => {
    // Arrange & Act
    renderModal(() => {}, { dayTimes: [] })

    // Assert
    const days = await loadedDays()
    expect(days.every((day) => (day as HTMLButtonElement).disabled)).toBe(true)
    expect(TODAY).toBe('2026-10-19')
  })
})
