import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BookingModal from '../BookingModal.vue'
import { SLUG, TODAY, installFakeScheduleApi, requestRow, type ScheduleOptions } from './fakeScheduleApi'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import type { BarbershopAddress, ScheduleSettings } from '@/Domain/Settings/settings.model'
import { createTestSettings } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { renderWithPlugins } from '@/utils/testRender'

/** Renders the modal on the public page of SLUG, with its booking options changed by `change`. */
function renderModal(
  change: (schedule: ScheduleSettings) => void = () => {},
  api: ScheduleOptions = {},
  address: BarbershopAddress | null = createTestSettings().profile.address,
) {
  const fake = installFakeScheduleApi(api)
  const rendered = renderWithPlugins(BookingModal, {}, () => {
    const settings = createTestSettings()
    const { schedule } = settings
    schedule.services = schedule.services.map(({ costInCents: _cost, ...service }) => service)
    change(schedule)
    const store = usePublicPageStore()
    store.slug = SLUG
    store.bookingOptions = schedule
    store.page = {
      profile: { ...settings.profile, avatarUrl: '', backgroundUrl: '', address },
      portfolio: { coverUrl: '', quote: '', story: '', highlights: [], photos: [] },
      theme: settings.theme,
      rating: { average: null, count: 0 },
      featuredReviews: [],
    }
  })
  return { ...rendered, ...fake }
}

/** The device answers the location request with `point`, or with an error code (1 = denied). */
function fakeDeviceLocation(answer: { latitude: number; longitude: number } | { errorCode: number }) {
  const getCurrentPosition = vi.fn((success: PositionCallback, failure: PositionErrorCallback) => {
    if ('errorCode' in answer) failure({ code: answer.errorCode, PERMISSION_DENIED: 1 } as GeolocationPositionError)
    else success({ coords: answer } as GeolocationPosition)
  })
  Object.defineProperty(navigator, 'geolocation', { value: { getCurrentPosition }, configurable: true })
  return getCurrentPosition
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
  await fireEvent.click(screen.getByRole('checkbox', { name: /^Corte(?! infantil)/ }))
  // Tomorrow (Tuesday): today's times may have passed.
  await fireEvent.click((await loadedDays())[1]!)
  await fireEvent.click(await screen.findByRole('radio', { name: '11:00' }))
  await fireEvent.update(screen.getByLabelText('Nome'), 'João Silva')
  await fireEvent.update(screen.getByLabelText('WhatsApp'), '(11) 91234-5678')
  await fireEvent.update(screen.getByLabelText('E-mail'), 'joao@example.com')
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
    await fireEvent.click(screen.getByRole('checkbox', { name: /^Corte(?! infantil)/ }))

    // Assert
    expect((submit as HTMLButtonElement).disabled).toBe(true)
  })

  it('asks the API for the free times of the chosen service', async () => {
    // Arrange
    const { calls } = renderModal()

    // Act
    await fireEvent.click(screen.getByRole('checkbox', { name: /^Corte(?! infantil)/ }))

    // Assert
    await vi.waitFor(() =>
      expect(calls.map((call) => call.path)).toEqual([`/public/${SLUG}/availability`, `/public/${SLUG}/availability?service_ids[]=1`]),
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
      client_email: 'joao@example.com',
      service_ids: [1],
      client_notes: null,
      date: '2026-10-20',
      time: '11:00',
      client_latitude: null,
      client_longitude: null,
    })
    expect(requests.at(-1)?.status).toBe('pending')
  })

  it('after booking, invites the client to complete the sign-up with the e-mail filled in', async () => {
    // Arrange
    renderModal()
    await fillValidBooking()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))

    // Assert
    const card = await screen.findByRole('link', { name: /Finalize seu cadastro/ })
    expect(card.getAttribute('href')).toBe('/conta/finalizar?email=joao@example.com')
  })

  it('keeps the submit button disabled while the e-mail is invalid', async () => {
    // Arrange
    renderModal()
    await fillValidBooking()

    // Act
    await fireEvent.update(screen.getByLabelText('E-mail'), 'joao@')

    // Assert
    expect((screen.getByRole('button', { name: 'Solicitar Agendamento' }) as HTMLButtonElement).disabled).toBe(true)
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
    expect(screen.getByRole('checkbox', { name: /Corte infantil/ })).toBeTruthy()
    expect(screen.queryByRole('checkbox', { name: /Barba/ })).toBeNull()
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

describe('BookingModal and the barbershop address', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('shows where the barbershop is, with directions', () => {
    // Arrange & Act
    renderModal()

    // Assert
    expect(screen.getByText('Rua Augusta, 1500 – Consolação, São Paulo/SP')).toBeTruthy()
    const directions = screen.getByRole('link', { name: /Como chegar/ })
    expect(directions.getAttribute('href')).toContain('https://www.google.com/maps/search/?api=1&query=Rua%20Augusta')
    expect(directions.getAttribute('target')).toBe('_blank')
  })

  it('the location is asked on "Solicitar Agendamento", and sharing sends the coordinates', async () => {
    // Arrange: the MASP, 648 m from the barbershop
    fakeDeviceLocation({ latitude: -23.5614, longitude: -46.6559 })
    const { calls } = renderModal()
    await fillValidBooking()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))
    const question = screen.getByText('Quer avisar o barbeiro a que distância você está?')
    await fireEvent.click(screen.getByRole('button', { name: 'Compartilhar e enviar' }))

    // Assert
    expect(question).toBeTruthy()
    await screen.findByText('Solicitação enviada!')
    expect(calls.at(-1)?.body).toMatchObject({ client_latitude: -23.5614, client_longitude: -46.6559 })
  })

  it('"Enviar sem localização" books without asking the device', async () => {
    // Arrange
    const getCurrentPosition = fakeDeviceLocation({ latitude: -23.5614, longitude: -46.6559 })
    const { calls } = renderModal()
    await fillValidBooking()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar sem localização' }))

    // Assert
    await screen.findByText('Solicitação enviada!')
    expect(getCurrentPosition).not.toHaveBeenCalled()
    expect(calls.at(-1)?.body).toMatchObject({ client_latitude: null, client_longitude: null })
  })

  it('a client who denies the location still books, without coordinates', async () => {
    // Arrange
    fakeDeviceLocation({ errorCode: 1 })
    const { calls } = renderModal()
    await fillValidBooking()

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Compartilhar e enviar' }))

    // Assert
    await screen.findByText('Solicitação enviada!')
    expect(calls.at(-1)?.body).toMatchObject({ client_latitude: null, client_longitude: null })
  })

  it('several services add up time and price, and the note goes to the barber', async () => {
    // Arrange
    fakeDeviceLocation({ errorCode: 1 })
    const { calls } = renderModal()
    await fireEvent.click(screen.getByRole('checkbox', { name: /^Corte(?! infantil)/ }))
    await fireEvent.click(screen.getByRole('checkbox', { name: /^Barba/ }))
    const total = screen.getByText(/^Total:/).textContent
    await fireEvent.click((await loadedDays())[1]!)
    await fireEvent.click(await screen.findByRole('radio', { name: '11:00' }))
    await fireEvent.update(screen.getByLabelText('Nome'), 'João Silva')
    await fireEvent.update(screen.getByLabelText('WhatsApp'), '(11) 91234-5678')
    await fireEvent.update(screen.getByLabelText('E-mail'), 'joao@example.com')
    await fireEvent.update(screen.getByLabelText('Observação para o barbeiro (opcional)'), 'Cabelo cacheado')

    // Act
    await fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))
    await fireEvent.click(screen.getByRole('button', { name: 'Enviar sem localização' }))

    // Assert
    expect(total?.replace(/\s/g, ' ')).toBe('Total: 1h15 · R$ 90,00')
    await screen.findByText('Solicitação enviada!')
    expect(calls.find((call) => call.path.startsWith('/public/rafael/availability?service_ids[]=1&service_ids[]=2'))).toBeTruthy()
    expect(calls.at(-1)?.body).toMatchObject({ service_ids: [1, 2], client_notes: 'Cabelo cacheado' })
  })

  it('a barbershop without address shows a notice instead of the form', () => {
    // Arrange & Act
    renderModal(() => {}, {}, null)

    // Assert
    expect(screen.getByText('Agenda indisponível')).toBeTruthy()
    expect(screen.getByText(/ainda não informou o endereço/)).toBeTruthy()
    expect(screen.queryByRole('button', { name: 'Solicitar Agendamento' })).toBeNull()
  })
})
