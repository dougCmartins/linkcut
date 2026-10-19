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

/**
 * Corner style of the buttons, from the Figma kit (36px-tall buttons):
 * square = 0px, rounded = 8px (8/36 of the height), pill = fully round.
 * The ratio itself lives in `styles/core.css` (`--button-roundness`).
 */
export const ButtonShape = {
  Square: 'square',
  Rounded: 'rounded',
  Pill: 'pill',
} as const

export type ButtonShape = (typeof ButtonShape)[keyof typeof ButtonShape]

export const BUTTON_SHAPES: ButtonShape[] = Object.values(ButtonShape)

/**
 * Look of the main buttons. Elevated is the original LinkCut 3D button;
 * the others come from the Figma kit (fill, outline, hard shadow, soft shadow).
 */
export const ButtonStyle = {
  Elevated: 'elevated',
  Fill: 'fill',
  Outline: 'outline',
  HardShadow: 'hardShadow',
  SoftShadow: 'softShadow',
} as const

export type ButtonStyle = (typeof ButtonStyle)[keyof typeof ButtonStyle]

export const BUTTON_STYLES: ButtonStyle[] = Object.values(ButtonStyle)
