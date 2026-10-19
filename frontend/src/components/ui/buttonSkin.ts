import { ButtonStyle } from '@/types/theme'

/** primary = accent button (main action); secondary = neutral button. */
export type ButtonTone = 'primary' | 'secondary'

const PRESS = 'hover:-translate-y-0.5 active:translate-y-0 disabled:translate-y-0'

/**
 * Classes for each button style chosen in Settings > Appearance.
 * Border and shadow sizes come from the `button-*` utilities in styles/core.css,
 * which keep the Figma kit proportions at any button height.
 */
const SKINS: Record<ButtonStyle, Record<ButtonTone, string>> = {
  // Original LinkCut 3D look: colored edge under the button.
  [ButtonStyle.Elevated]: {
    primary:
      'bg-accent text-on-accent [--edge:var(--accent-edge)] shadow-[0_6px_0_0_var(--edge),var(--shadow-soft)] hover:-translate-y-0.5 hover:shadow-[0_8px_0_0_var(--edge),var(--shadow-float)] active:translate-y-1 active:shadow-[0_2px_0_0_var(--edge)] disabled:translate-y-0',
    secondary:
      'bg-surface text-ink ring-1 ring-line [--edge:var(--neutral-edge)] shadow-[0_6px_0_0_var(--edge),var(--shadow-soft)] hover:-translate-y-0.5 hover:shadow-[0_8px_0_0_var(--edge),var(--shadow-float)] active:translate-y-1 active:shadow-[0_2px_0_0_var(--edge)] disabled:translate-y-0',
  },
  // Figma "Fill": solid color, no border, no shadow.
  [ButtonStyle.Fill]: {
    primary: `bg-accent text-on-accent hover:brightness-110 ${PRESS}`,
    secondary: `bg-ink text-page hover:brightness-110 ${PRESS}`,
  },
  // Figma "Outline": no fill, thin border.
  [ButtonStyle.Outline]: {
    primary: `bg-transparent text-accent button-border [--button-line:var(--accent)] hover:bg-accent/10 ${PRESS}`,
    secondary: `bg-transparent text-ink button-border [--button-line:var(--ink)] hover:bg-ink/5 ${PRESS}`,
  },
  // Figma "Hard shadow": border plus a solid diagonal shadow; pressing slides onto the shadow.
  // The accent button uses its edge color: in palettes where the accent is the text color
  // (e.g. light Default), an ink shadow would merge with the button.
  [ButtonStyle.HardShadow]: {
    primary:
      'bg-accent text-on-accent button-border button-hard-shadow button-press-into-shadow [--button-line:var(--accent-edge)]',
    secondary: 'bg-surface text-ink button-border button-hard-shadow button-press-into-shadow [--button-line:var(--ink)]',
  },
  // Figma "Soft shadow": no border, blurred shadow below.
  [ButtonStyle.SoftShadow]: {
    primary: `bg-accent text-on-accent button-soft-shadow ${PRESS}`,
    secondary: `bg-surface text-ink button-soft-shadow ${PRESS}`,
  },
}

export function buttonSkinClasses(style: ButtonStyle, tone: ButtonTone): string {
  return SKINS[style][tone]
}
