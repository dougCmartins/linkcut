import { readonly, ref } from 'vue'
import type { ThemeSettings } from '@/Domain/Settings/settings.model'
import { ThemeMode, type ButtonShape, type ThemePalette } from '@/types/theme'
import { darken, isHexColor, readableTextOn } from '@/utils/color'

const CUSTOM_ACCENT_VARIABLES = ['--accent', '--on-accent', '--accent-edge'] as const
const ACCENT_EDGE_DARKEN = 0.35
const VISITOR_MODE_KEY = 'linkcut.visitorMode'

export interface ThemeAttributes {
  'data-theme': ThemePalette
  'data-mode': ThemeMode
  'data-button-shape': ButtonShape
  style: Record<string, string>
}

/** CSS variables for a custom accent: the color, a readable text color on top of it and the button edge. */
export function customAccentVariables(accent: string | null): Record<string, string> {
  if (!accent || !isHexColor(accent)) return {}
  return {
    '--accent': accent,
    '--on-accent': readableTextOn(accent),
    '--accent-edge': darken(accent, ACCENT_EDGE_DARKEN),
  }
}

/** Attributes that theme any element (used on <html> and on the settings previews). */
export function themeAttributes(theme: ThemeSettings, modeOverride: ThemeMode | null = null): ThemeAttributes {
  return {
    'data-theme': theme.palette,
    'data-mode': modeOverride ?? theme.mode,
    'data-button-shape': theme.buttonShape,
    style: customAccentVariables(theme.customAccent),
  }
}

export function applyThemeToDocument(theme: ThemeSettings, modeOverride: ThemeMode | null = null): void {
  const root = document.documentElement
  const attributes = themeAttributes(theme, modeOverride)
  root.dataset.theme = attributes['data-theme']
  root.dataset.mode = attributes['data-mode']
  root.dataset.buttonShape = attributes['data-button-shape']
  CUSTOM_ACCENT_VARIABLES.forEach((name) => {
    const value = attributes.style[name]
    if (value) root.style.setProperty(name, value)
    else root.style.removeProperty(name)
  })
}

function readVisitorMode(): ThemeMode | null {
  try {
    const stored = localStorage.getItem(VISITOR_MODE_KEY)
    return stored === ThemeMode.Dark || stored === ThemeMode.Light ? stored : null
  } catch {
    return null
  }
}

const visitorMode = ref<ThemeMode | null>(readVisitorMode())

/** Light/dark choice of the person visiting the public pages. Overrides the barber's default for them only. */
export function useVisitorMode() {
  function setVisitorMode(mode: ThemeMode): void {
    visitorMode.value = mode
    try {
      localStorage.setItem(VISITOR_MODE_KEY, mode)
    } catch {
      // Storage blocked: the choice still applies for this visit.
    }
  }

  function toggleVisitorMode(currentMode: ThemeMode): void {
    setVisitorMode(currentMode === ThemeMode.Dark ? ThemeMode.Light : ThemeMode.Dark)
  }

  return { visitorMode: readonly(visitorMode), setVisitorMode, toggleVisitorMode }
}
