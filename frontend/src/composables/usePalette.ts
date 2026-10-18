import { readonly, ref } from 'vue'
import { Palette } from '@/types/palette'

const STORAGE_KEY = 'linkcut.palette'
const palette = ref<Palette>(readStoredPalette())

function readStoredPalette(): Palette {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === Palette.Light ? Palette.Light : Palette.Dark
  } catch {
    return Palette.Dark
  }
}

function applyPalette(value: Palette): void {
  palette.value = value
  document.documentElement.dataset.palette = value
  try {
    localStorage.setItem(STORAGE_KEY, value)
  } catch {
    // Storage may be blocked (private mode); the palette still applies for this visit.
  }
}

export function usePalette() {
  function togglePalette(): void {
    applyPalette(palette.value === Palette.Dark ? Palette.Light : Palette.Dark)
  }

  return {
    palette: readonly(palette),
    applyPalette,
    togglePalette,
  }
}
