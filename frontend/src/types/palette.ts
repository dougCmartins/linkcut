export const Palette = {
  Dark: 'dark',
  Light: 'light',
} as const

export type Palette = (typeof Palette)[keyof typeof Palette]
