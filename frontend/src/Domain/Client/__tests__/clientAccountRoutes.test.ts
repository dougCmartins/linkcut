import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import ClientAppointmentCard from '../ClientAppointmentCard.vue'
import type { ClientAppointment } from '../client.model'
import { routes } from '@/router'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { renderWithPlugins } from '@/utils/testRender'

const VISITED_SLUG_KEY = 'linkcut.visited-slug'

async function landingOf(path: string): Promise<string> {
  const router = createRouter({ history: createMemoryHistory(), routes })
  await router.push(path)
  return router.currentRoute.value.fullPath
}

describe('old /conta addresses (e-mails and pushes already sent)', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('open the client area of the last barbershop opened on this device', async () => {
    // Arrange
    localStorage.setItem(VISITED_SLUG_KEY, 'rafael')

    // Act
    const home = await landingOf('/conta')
    const profile = await landingOf('/conta/perfil')
    const review = await landingOf('/conta/review/7')
    const list = await landingOf('/conta/barbearias')

    // Assert
    expect(home).toBe('/rafael/account')
    expect(profile).toBe('/rafael/account/profile')
    expect(review).toBe('/rafael/account/review/7')
    expect(list).toBe('/account')
  })

  it('without any barbershop opened here, open the list of barbershops', async () => {
    // Arrange / Act
    const home = await landingOf('/conta')

    // Assert
    expect(home).toBe('/account')
  })
})

describe('ClientAppointmentCard status', () => {
  const appointment = (status: AppointmentStatus): ClientAppointment => ({
    id: 3,
    serviceName: 'Barba',
    date: '2026-10-21',
    time: '14:30',
    endTime: '15:00',
    status,
    hasReview: false,
    barbershop: { name: 'Rafael Costa', slug: 'rafael', address: '', mapsUrl: '', whatsappUrl: '', avatarUrl: null },
  })

  it('a request still waiting after its day passed reads "Não confirmado"', () => {
    // Arrange / Act
    const past = renderWithPlugins(ClientAppointmentCard, { props: { appointment: appointment(AppointmentStatus.Pending), isUpcoming: false } })
    const pastText = past.container.textContent
    past.unmount()
    const upcoming = renderWithPlugins(ClientAppointmentCard, { props: { appointment: appointment(AppointmentStatus.Pending), isUpcoming: true } })

    // Assert
    expect(pastText).toContain('Não confirmado')
    expect(pastText).not.toContain('Aguardando')
    expect(upcoming.container.textContent).toContain('Aguardando')
  })
})
