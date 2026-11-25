import { onBeforeUnmount, ref, watch, type Ref } from 'vue'
import { checkSlug } from './onboarding.api'
import { SLUG_MAX_LENGTH, isReservedSlug, isValidSlug } from './slug'

export type SlugStatus = 'idle' | 'checking' | 'available' | 'taken' | 'invalid'

const SLUG_CHECK_DELAY_MS = 400

/**
 * Checks a public link while it is typed (sign-up and "Nova filial"): the format and the reserved names here,
 * the rest on the API after a short pause. A taken link comes with a free one close to it.
 */
export function useSlugCheck(slug: Ref<string>) {
  const status = ref<SlugStatus>('idle')
  const suggestion = ref('')
  let timer: ReturnType<typeof setTimeout> | undefined
  let request = 0

  async function verify(value: string): Promise<void> {
    const current = ++request
    try {
      const answer = await checkSlug(value)
      if (current !== request || value !== slug.value) return
      status.value = answer.available ? 'available' : 'taken'
      suggestion.value = answer.available ? '' : answer.suggestion
    } catch {
      if (current === request) status.value = 'idle'
    }
  }

  watch(slug, (value) => {
    clearTimeout(timer)
    suggestion.value = ''
    if (!value) return void (status.value = 'idle')
    if (!isValidSlug(value)) return void (status.value = 'invalid')
    if (isReservedSlug(value)) {
      status.value = 'taken'
      suggestion.value = `${value}-barbearia`.slice(0, SLUG_MAX_LENGTH)
      return
    }
    status.value = 'checking'
    timer = setTimeout(() => void verify(value), SLUG_CHECK_DELAY_MS)
  })

  onBeforeUnmount(() => clearTimeout(timer))

  /** Asks the API again (e.g. the link was taken between the check and the save). */
  function recheck(): void {
    if (slug.value) void verify(slug.value)
  }

  return { status, suggestion, recheck }
}
