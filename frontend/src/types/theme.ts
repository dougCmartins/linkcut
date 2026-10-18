export const ThemeMode = {
  Dark: 'dark',
  Light: 'light',
} as const

export type ThemeMode = (typeof ThemeMode)[keyof typeof ThemeMode]

/** Preset palettes. Their tokens live in `styles/core.css`. */
export const ThemePalette = {
  Default: 'default',
  Classic: 'classic',
  Ocean: 'ocean',
  Forest: 'forest',
  Wine: 'wine',
  Minimal: 'minimal',
} as const

export type ThemePalette = (typeof ThemePalette)[keyof typeof ThemePalette]

export const THEME_PALETTES: ThemePalette[] = Object.values(ThemePalette)
