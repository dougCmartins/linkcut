import { afterEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import BookingModal from '../BookingModal.vue'
import { SLUG, installFakeScheduleApi, requestRow } from './fakeScheduleApi'
import type { PublicStaff } from '../booking.model'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { createTestSettings } from '@/Domain/Settings/__tests__/fakeSettingsApi'
import { envelope, type FakeCall } from '@/utils/testFetch'
import { renderWithPlugins } from '@/utils/testRender'

const RAFAEL: PublicStaff = { id: 1, name: 'Rafael', avatar: null }
const BRUNO: PublicStaff = { id: 2, name: 'Bruno', avatar: null }
/** Bruno does not do "Barba" (service 2). */
const DOES_BARBA = [RAFAEL]

/** The modal of SLUG with these active barbers; the booking API answers who attends (Bruno for "any"). */
function renderModal(staff: PublicStaff[], options: { takenFor?: number } = {}) {
  const fake = installFakeScheduleApi({}, {
    [`GET /public/${SLUG}/staff`]: ({ query }: FakeCall) => ({
      status: 200,
      body: query.getAll('service_ids[]').includes('2') ? DOES_BARBA : staff,
    }),
    [`POST /public/${SLUG}/booking-requests`]: ({ body }: FakeCall) => {
      const staffId = body?.staff_id as number | undefined
      if (options.takenFor !== undefined && staffId === options.takenFor) return envelope('SLOT_UNAVAILABLE', 409)
      const row = requestRow({ id: 900, date: String(body?.date), starts_at: String(body?.time) })
      const name = staff.find((member) => member.id === staffId)?.name ?? BRUNO.name
      return {
        status: 201,
        body: { id: row.id, service_name: row.service_name, date: row.date, time: row.starts_at, status: 'pending', staff_name: name },
      }
    },
  })
  const rendered = renderWithPlugins(BookingModal, {}, () => {
    const settings = createTestSettings()
    const store = usePublicPageStore()
    store.slug = SLUG
    store.bookingOptions = settings.schedule
    store.staff = staff
    store.page = {
      profile: { ...settings.profile, avatarUrl: '', backgroundUrl: '' },
      portfolio: { coverUrl: '', quote: '', story: '', highlights: [], photos: [] },
      theme: settings.theme,
      rating: { average: null, count: 0 },
      featuredReviews: [],
    }
  })
  return { ...rendered, ...fake }
}

const dayButtons = () => screen.getAllByRole('radio').filter((element) => element.getAttribute('aria-label'))
const loadedDays = () =>
  vi.waitFor(() => {
    const days = dayButtons()
    expect(days).toHaveLength(7)
    return days
  })

async function chooseCorteTomorrowAt11(): Promise<void> {
  await fireEvent.click(screen.getByRole('checkbox', { name: /^Corte(?! infantil)/ }))
  await fireEvent.click((await loadedDays())[1]!)
  await fireEvent.click(await screen.findByRole('radio', { name: '11:00' }))
}

async function fillContact(): Promise<void> {
  await fireEvent.update(screen.getByLabelText('Nome'), 'João Silva')
  await fireEvent.update(screen.getByLabelText('WhatsApp'), '(11) 91234-5678')
  await fireEvent.update(screen.getByLabelText('E-mail'), 'joao@example.com')
}

const send = () => fireEvent.click(screen.getByRole('button', { name: 'Solicitar Agendamento' }))
const sendWithoutLocation = async () => {
  await send()
  const without = screen.queryByRole('button', { name: 'Enviar sem localização' })
  if (without) await fireEvent.click(without)
}

describe('BookingModal: choosing the barber', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.unstubAllEnvs()
  })

  it('a unit with one barber shows no picker and never asks for the staff', async () => {
    // Arrange & Act
    const { calls } = renderModal([RAFAEL])
    await fireEvent.click(screen.getByRole('checkbox', { name: /^Corte(?! infantil)/ }))
    await loadedDays()

    // Assert
    expect(screen.queryByText('Escolha o barbeiro')).toBeNull()
    expect(calls.some((call) => call.path.includes('/staff'))).toBe(false)
  })

  it('a unit with two barbers shows the picker with "Qualquer um disponível" chosen', async () => {
    // Arrange & Act
    renderModal([RAFAEL, BRUNO])

    // Assert
    expect(screen.getByText('Escolha o barbeiro')).toBeTruthy()
    expect(screen.getByRole('radio', { name: 'Qualquer um disponível' }).getAttribute('aria-checked')).toBe('true')
    expect(screen.getByRole('radio', { name: /Bruno/ }).getAttribute('aria-checked')).toBe('false')
  })

  it('a chosen barber goes in the free times and in the booking, and the confirmation names them', async () => {
    // Arrange
    const { calls } = renderModal([RAFAEL, BRUNO])
    await chooseCorteTomorrowAt11()

    // Act
    await fireEvent.click(await screen.findByRole('radio', { name: /Rafael/ }))
    await vi.waitFor(() => expect(calls.some((call) => call.path.includes('staff_id=1'))).toBe(true))
    await fireEvent.click(await screen.findByRole('radio', { name: '11:00' }))
    await fillContact()
    await sendWithoutLocation()

    // Assert
    await screen.findByText(/com Rafael/)
    const availability = calls.filter((call) => call.path.startsWith(`/public/${SLUG}/availability`)).at(-1)
    expect(availability?.query.get('staff_id')).toBe('1')
    expect(calls.at(-1)?.body).toMatchObject({ staff_id: 1 })
  })

  it('"Qualquer um" sends no barber and still shows who will attend', async () => {
    // Arrange
    const { calls } = renderModal([RAFAEL, BRUNO])
    await chooseCorteTomorrowAt11()
    await fillContact()

    // Act
    await sendWithoutLocation()

    // Assert
    await screen.findByText(/com Bruno/)
    expect(calls.filter((call) => call.path.includes('/availability')).every((call) => !call.query.has('staff_id'))).toBe(true)
    expect(calls.at(-1)?.body).not.toHaveProperty('staff_id')
  })

  it('a barber who does not do a newly chosen service goes back to "Qualquer um"', async () => {
    // Arrange
    renderModal([RAFAEL, BRUNO])
    await fireEvent.click(screen.getByRole('radio', { name: /Bruno/ }))

    // Act
    await fireEvent.click(screen.getByRole('checkbox', { name: /^Barba/ }))

    // Assert
    const any = await screen.findByRole('radio', { name: 'Qualquer um disponível' })
    expect(any.getAttribute('aria-checked')).toBe('true')
    expect(screen.queryByRole('radio', { name: /Bruno/ })).toBeNull()
  })

  it('SLOT_UNAVAILABLE for the chosen barber says so and loads their free times again', async () => {
    // Arrange
    const { calls } = renderModal([RAFAEL, BRUNO], { takenFor: 2 })
    await chooseCorteTomorrowAt11()
    await fireEvent.click(screen.getByRole('radio', { name: /Bruno/ }))
    await fireEvent.click(await screen.findByRole('radio', { name: '11:00' }))
    await fillContact()
    const before = calls.length

    // Act
    await sendWithoutLocation()

    // Assert
    expect(await screen.findByRole('alert')).toBeTruthy()
    await vi.waitFor(() =>
      expect(calls.slice(before).some((call) => call.path.includes('/availability') && call.query.get('staff_id') === '2')).toBe(true),
    )
  })
})
