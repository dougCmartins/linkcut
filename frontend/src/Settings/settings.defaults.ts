import type { AppSettings, DayHours, ThemeSettings } from './settings.model'
import { SocialNetwork } from '@/types/socialNetwork'
import { ButtonShape, ButtonStyle, ThemeMode, ThemePalette } from '@/types/theme'

/** Initial settings for a new barber (also used to restore defaults and to fill missing fields). */

const unsplash = (id: string, width: number) =>
  `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${width}&q=75`

const SUNDAY = 0
const SATURDAY = 6

function defaultWeeklyHours(): DayHours[] {
  return Array.from({ length: 7 }, (_, weekday) => ({
    weekday,
    isOpen: weekday !== SUNDAY,
    opensAt: '09:00',
    closesAt: weekday === SATURDAY ? '14:00' : '19:00',
  }))
}

export const DEFAULT_THEME: ThemeSettings = {
  palette: ThemePalette.Default,
  mode: ThemeMode.Dark,
  customAccent: null,
  buttonShape: ButtonShape.Pill,
  buttonStyle: ButtonStyle.Elevated,
}

export function createDefaultSettings(): AppSettings {
  return {
    profile: {
      name: 'Rafael Costa',
      handle: '@rafa.barber',
      bio: 'Cortes clássicos e degradês precisos. Atendimento exclusivo com hora marcada.',
      avatar: unsplash('1506794778202-cad84cf45f1d', 320),
      background: unsplash('1503951914875-452162b0f3f1', 1200),
      whatsappNumber: '11999998888',
      whatsappMessage: 'Olá! Vim pelo seu link e tenho uma dúvida.',
      socials: [
        { network: SocialNetwork.Instagram, url: 'https://instagram.com/' },
        { network: SocialNetwork.TikTok, url: 'https://tiktok.com/' },
        { network: SocialNetwork.YouTube, url: 'https://youtube.com/' },
      ],
    },
    portfolio: {
      cover: unsplash('1503951914875-452162b0f3f1', 1200),
      quote: 'Um bom corte não muda quem você é. Ele mostra, com precisão, quem você já é.',
      story: [
        'Comecei na cadeira do meu pai, aos 16 anos, aprendendo que cada cliente chega com uma história e sai com uma nova confiança.',
        'Hoje atendo sozinho, um cliente por vez, sem pressa. Técnica clássica, acabamento na navalha e produtos premium.',
      ].join('\n\n'),
      highlights: [
        { label: 'Estilo', value: 'Clássico moderno' },
        { label: 'Foco', value: 'Degradê & barba' },
        { label: 'Agenda', value: 'Exclusiva' },
      ],
      photos: [
        { id: 'work-1', src: unsplash('1621605815971-fbc98d665033', 600), alt: 'Degradê com risca lateral' },
        { id: 'work-2', src: unsplash('1599351431202-1e0f0137899a', 600), alt: 'Barba desenhada na navalha' },
        { id: 'work-3', src: unsplash('1605497788044-5a32c7078486', 600), alt: 'Corte social com textura' },
        { id: 'work-4', src: unsplash('1585747860715-2ba37e788b70', 600), alt: 'Cadeira da barbearia' },
      ],
    },
    schedule: {
      services: [
        { id: 'haircut', name: 'Corte', durationMinutes: 45, priceInCents: 5000, costInCents: 600 },
        { id: 'beard', name: 'Barba', durationMinutes: 30, priceInCents: 4000, costInCents: 400 },
      ],
      weeklyHours: defaultWeeklyHours(),
      slotIntervalMinutes: 30,
      bookingWindowDays: 7,
      vacation: {
        isEnabled: false,
        startsOn: null,
        endsOn: null,
        message: 'Estou de férias! Volto em breve com a agenda aberta.',
      },
    },
    theme: { ...DEFAULT_THEME },
    security: { defaultTwoFactorChannel: null },
  }
}
