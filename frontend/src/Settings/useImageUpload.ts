import { ref } from 'vue'
import { useSettingsStore } from './settings.store'

/** Uploads an image for a settings field and tracks which fields are busy or failed. */
export function useImageUpload() {
  const store = useSettingsStore()
  const busyFields = ref(new Set<string>())
  const failedField = ref<string | null>(null)

  async function upload(field: string, file: File, assign: (source: string) => void): Promise<void> {
    busyFields.value.add(field)
    failedField.value = null
    try {
      assign(await store.uploadImage(file))
    } catch {
      failedField.value = field
    } finally {
      busyFields.value.delete(field)
    }
  }

  const isBusy = (field: string) => busyFields.value.has(field)

  return { upload, isBusy, failedField }
}
