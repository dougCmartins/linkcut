import { useI18n } from 'vue-i18n'
import { useSettingsStore } from './settings.store'
import type { SettingsSection } from './settings.model'

/** Translated validation message for a field of a settings section, or undefined. */
export function useSectionErrors(section: SettingsSection) {
  const store = useSettingsStore()
  const { t } = useI18n()

  function errorFor(field: string): string | undefined {
    const error = store.sectionErrors(section)[field]
    return error ? t(`settings.errors.${error.key}`, error.params ?? {}) : undefined
  }

  return { errorFor }
}
