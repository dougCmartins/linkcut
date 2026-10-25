import { computed } from 'vue'
import { useRoute, useRouter, type Router } from 'vue-router'

/**
 * Goes back when the previous history entry is `target` (the overlay was opened from it), otherwise replaces the
 * address. So "fechar" never leaves the app, and the phone's back button closes the overlay instead of the page,
 * which matters in Instagram and WhatsApp browsers, where leaving the first page closes everything.
 */
export function leaveTo(router: Router, target: string): void {
  const back: unknown = window.history.state?.back
  if (back === target) router.back()
  else void router.replace(target)
}

/** An overlay (share sheet, legal text) opened as `?key=value` on the current page, with its own history entry. */
export function useQueryOverlay(key: string) {
  const route = useRoute()
  const router = useRouter()

  const value = computed(() => {
    const raw = route.query[key]
    return typeof raw === 'string' ? raw : null
  })

  function open(next = '1'): void {
    void router.push({ query: { ...route.query, [key]: next } })
  }

  function close(): void {
    const rest = { ...route.query }
    delete rest[key]
    leaveTo(router, router.resolve({ path: route.path, query: rest, hash: route.hash }).fullPath)
  }

  return { value, isOpen: computed(() => value.value !== null), open, close }
}
