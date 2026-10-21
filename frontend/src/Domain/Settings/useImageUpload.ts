import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from './settings.store'
import type { ImageRef } from './settings.model'
import { useApiErrors } from '@/composables/useApiErrors'
import { ApiErrorCode } from '@/types/apiErrorCode'
import { preloadImage } from '@/utils/image'

/** The image was accepted by the API but the browser could not display it. */
class ImageNotDisplayedError extends Error {}

/**
 * Uploads the image of a settings field. The field only changes when the API accepted the image AND it really
 * displays; otherwise `revert` puts back the last saved value and the error shows under that field.
 */
export function useImageUpload() {
  const store = useSettingsStore()
  const { t } = useI18n()
  const apiErrors = useApiErrors()
  const busyFields = ref(new Set<string>())
  const errors = ref<Record<string, string>>({})

  function messageFor(caught: unknown): string {
    if (caught instanceof ImageNotDisplayedError) return t('settings.image.notDisplayed')
    // File refused by the API rules (type, size, not a real image).
    if (apiErrors.hasErrorCode(caught, ApiErrorCode.ValidationFailed) || apiErrors.hasErrorCode(caught, ApiErrorCode.InvalidImage)) {
      return t('settings.image.refused')
    }
    const key = apiErrors.messageKey(caught)
    return key === 'unexpected' ? t('settings.image.uploadFailed') : t(`errors.api.${key}`)
  }

  async function upload(field: string, file: File, assign: (image: ImageRef) => void, revert?: () => void): Promise<boolean> {
    busyFields.value.add(field)
    clearError(field)
    try {
      const image = await store.uploadImage(file)
      try {
        await preloadImage(image.url)
      } catch {
        throw new ImageNotDisplayedError()
      }
      assign(image)
      return true
    } catch (caught) {
      revert?.()
      errors.value = { ...errors.value, [field]: messageFor(caught) }
      return false
    } finally {
      busyFields.value.delete(field)
    }
  }

  function clearError(field: string): void {
    if (!(field in errors.value)) return
    const { [field]: _removed, ...rest } = errors.value
    errors.value = rest
  }

  const isBusy = (field: string) => busyFields.value.has(field)
  const errorFor = (field: string): string | undefined => errors.value[field]

  return { upload, isBusy, errorFor, clearError }
}
