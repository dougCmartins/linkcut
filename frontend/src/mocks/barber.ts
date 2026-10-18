import type { BookingRequest, Service } from '@/Booking/booking.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import { nextDays, toIsoDate } from '@/utils/date'
import { SocialNetwork } from '@/types/socialNetwork'

/** Mock data until the backend exists. Text content here is written by the barber, so it is not i18n. */

const unsplash = (id: string, width: number) =>
  `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${width}&q=75`

export interface SocialLink {
  network: SocialNetwork
  url: string
}

export interface PortfolioPhoto {
  id: string
  url: string
  alt: string
}

export interface Highlight {
  label: string
  value: string
}

export interface BarberProfile {
  name: string
  handle: string
  bio: string
  avatarUrl: string
  coverUrl: string
  whatsappNumber: string
  quote: string
  story: string[]
  highlights: Highlight[]
  socials: SocialLink[]
  portfolio: PortfolioPhoto[]
}

export const barberProfile: BarberProfile = {
  name: 'Rafael Costa',
  handle: '@rafa.barber',
  bio: 'Cortes clássicos e degradês precisos. Atendimento exclusivo com hora marcada.',
  avatarUrl: unsplash('1506794778202-cad84cf45f1d', 320),
  coverUrl: unsplash('1503951914875-452162b0f3f1', 1200),
  whatsappNumber: '5511999998888',
  quote: 'Um bom corte não muda quem você é. Ele mostra, com precisão, quem você já é.',
  story: [
    'Comecei na cadeira do meu pai, aos 16 anos, aprendendo que cada cliente chega com uma história e sai com uma nova confiança.',
    'Hoje atendo sozinho, um cliente por vez, sem pressa. Técnica clássica, acabamento na navalha e produtos premium.',
  ],
  highlights: [
    { label: 'Estilo', value: 'Clássico moderno' },
    { label: 'Foco', value: 'Degradê & barba' },
    { label: 'Agenda', value: 'Exclusiva' },
  ],
  socials: [
    { network: SocialNetwork.Instagram, url: 'https://instagram.com/' },
    { network: SocialNetwork.TikTok, url: 'https://tiktok.com/' },
    { network: SocialNetwork.YouTube, url: 'https://youtube.com/' },
  ],
  portfolio: [
    { id: 'work-1', url: unsplash('1621605815971-fbc98d665033', 600), alt: 'Degradê com risca lateral' },
    { id: 'work-2', url: unsplash('1599351431202-1e0f0137899a', 600), alt: 'Barba desenhada na navalha' },
    { id: 'work-3', url: unsplash('1605497788044-5a32c7078486', 600), alt: 'Corte social com textura' },
    { id: 'work-4', url: unsplash('1585747860715-2ba37e788b70', 600), alt: 'Cadeira da barbearia' },
  ],
}

export const services: Service[] = [
  { id: 'haircut', name: 'Corte', durationMinutes: 45, priceInCents: 5000 },
  { id: 'beard', name: 'Barba', durationMinutes: 30, priceInCents: 4000 },
]

const WORKING_TIMES = ['09:00', '10:00', '11:00', '13:30', '14:30', '15:30', '16:30', '18:00']
const SUNDAY = 0

/** Mock availability: closed on Sundays, a few slots taken depending on the day. */
export function availableTimes(date: Date): string[] {
  if (date.getDay() === SUNDAY) return []
  return WORKING_TIMES.filter((_, index) => (index + date.getDate()) % 4 !== 0)
}

const [today, tomorrow] = nextDays(2)

export const initialRequests: BookingRequest[] = [
  {
    id: 'req-1',
    clientName: 'Lucas Andrade',
    clientWhatsapp: '11988887777',
    serviceId: 'haircut',
    date: toIsoDate(today ?? new Date()),
    time: '14:30',
    status: AppointmentStatus.Pending,
  },
  {
    id: 'req-2',
    clientName: 'Bruno Lima',
    clientWhatsapp: '11977776666',
    serviceId: 'beard',
    date: toIsoDate(tomorrow ?? new Date()),
    time: '10:00',
    status: AppointmentStatus.Pending,
  },
  {
    id: 'req-3',
    clientName: 'Pedro Martins',
    clientWhatsapp: '11966665555',
    serviceId: 'haircut',
    date: toIsoDate(tomorrow ?? new Date()),
    time: '16:30',
    status: AppointmentStatus.Pending,
  },
]

/** Current month figures, in cents. */
export const monthlyFinances = {
  revenueInCents: 1_284_000,
  expensesInCents: 846_000,
}
