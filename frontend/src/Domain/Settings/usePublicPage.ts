import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePublicPageStore } from './publicPage.store'

/** Loads the barbershop of the current /:slug route (only when the slug changes). */
export function usePublicPage() {
  const route = useRoute()
  const store = usePublicPageStore()

  watch(
    () => route.params.slug,
    (slug) => {
      if (typeof slug === 'string') void store.load(slug)
    },
    { immediate: true },
  )

  return store
}
