import { emptyAddress, type AppSettings, type ThemeSettings } from './settings.model'
import { ButtonShape, ButtonStyle, ThemeMode, ThemePalette } from '@/types/theme'

/** The default settings of a new barbershop come from the API; here only what the SPA needs before it answers. */

export const DEFAULT_THEME: ThemeSettings = {
  palette: ThemePalette.Default,
  mode: ThemeMode.Dark,
  customAccent: null,
  buttonShape: ButtonShape.Pill,
  buttonStyle: ButtonStyle.Fill,
}

/** Placeholder while the settings load (nothing is shown or editable until then). */
export function createEmptySettings(): AppSettings {
  return {
    profile: {
      name: '',
      handle: '',
      bio: '',
      avatar: null,
      background: null,
      whatsappNumber: '',
      whatsappMessage: '',
      socials: [],
      address: emptyAddress(),
    },
    portfolio: { cover: null, quote: '', story: '', highlights: [], photos: [] },
    schedule: {
      services: [],
      weeklyHours: [],
      slotIntervalMinutes: 30,
      bookingWindowDays: 7,
      vacation: { isEnabled: false, startsOn: null, endsOn: null, message: '' },
    },
    theme: { ...DEFAULT_THEME },
  }
}
