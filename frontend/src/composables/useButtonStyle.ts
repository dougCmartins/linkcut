import { computed, inject, provide, type ComputedRef, type InjectionKey } from 'vue'
import { useSettingsStore } from '@/Settings/settings.store'
import type { ButtonStyle } from '@/types/theme'

const BUTTON_STYLE_KEY: InjectionKey<ComputedRef<ButtonStyle>> = Symbol('buttonStyle')

/** Overrides the button style for everything below (e.g. the settings preview shows the draft). */
export function provideButtonStyle(style: ComputedRef<ButtonStyle>): void {
  provide(BUTTON_STYLE_KEY, style)
}

/** Current button style: the nearest provided one, otherwise the barber's saved setting. */
export function useButtonStyle(): ComputedRef<ButtonStyle> {
  const provided = inject(BUTTON_STYLE_KEY, null)
  if (provided) return provided
  const settingsStore = useSettingsStore()
  return computed(() => settingsStore.theme.buttonStyle)
}
