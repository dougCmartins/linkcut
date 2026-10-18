/** WCAG minimum contrast for large text and UI components (buttons). */
export const MIN_UI_CONTRAST = 3

const HEX_COLOR = /^#([0-9a-f]{6})$/i

export function isHexColor(value: string): boolean {
  return HEX_COLOR.test(value)
}

export function hexToRgb(hex: string): [number, number, number] {
  const match = HEX_COLOR.exec(hex.trim())
  if (!match?.[1]) throw new Error(`Invalid hex color: ${hex}`)
  const value = parseInt(match[1], 16)
  return [(value >> 16) & 255, (value >> 8) & 255, value & 255]
}

function rgbToHex([red, green, blue]: [number, number, number]): string {
  return `#${[red, green, blue].map((channel) => Math.round(channel).toString(16).padStart(2, '0')).join('')}`
}

/** Relative luminance as defined by WCAG 2.x. */
export function relativeLuminance(hex: string): number {
  const [red, green, blue] = hexToRgb(hex).map((channel) => {
    const value = channel / 255
    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
  }) as [number, number, number]
  return 0.2126 * red + 0.7152 * green + 0.0722 * blue
}

export function contrastRatio(first: string, second: string): number {
  const [lighter, darker] = [relativeLuminance(first), relativeLuminance(second)].sort((a, b) => b - a) as [
    number,
    number,
  ]
  return (lighter + 0.05) / (darker + 0.05)
}

/** Black or white, whichever reads better on top of `background`. */
export function readableTextOn(background: string): '#000000' | '#ffffff' {
  return contrastRatio(background, '#000000') >= contrastRatio(background, '#ffffff') ? '#000000' : '#ffffff'
}

/** Darkens a color by mixing it with black. `amount` goes from 0 (same) to 1 (black). */
export function darken(hex: string, amount: number): string {
  const ratio = 1 - Math.min(Math.max(amount, 0), 1)
  const [red, green, blue] = hexToRgb(hex)
  return rgbToHex([red * ratio, green * ratio, blue * ratio])
}

/** Parses a computed CSS color ("#rrggbb" or "rgb(r, g, b)") into hex. Returns null when unknown. */
export function cssColorToHex(value: string): string | null {
  const trimmed = value.trim()
  if (isHexColor(trimmed)) return trimmed.toLowerCase()
  const rgb = /^rgba?\((\d+),\s*(\d+),\s*(\d+)/i.exec(trimmed)
  if (!rgb) return null
  return rgbToHex([Number(rgb[1]), Number(rgb[2]), Number(rgb[3])])
}
