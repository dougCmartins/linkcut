import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePublicPageStore } from './publicPage.store'

/** "?unidade=<slug>" on a brand link: the unit picked in the branch picker (docs/decisoes/0001 P2). */
export const BRAND_UNIT_QUERY = 'unidade'

/**
 * Loads the barbershop of the current /:slug route (only when the slug changes).
 * On a brand link (the main unit of a brand with branches) it also loads the brand's units, and a unit picked with
 * ?unidade= is shown in place of the main one, without leaving the page. A picked slug that is not one of the brand's
 * units is ignored.
 */
export function usePublicPage() {
  const route = useRoute()
  const store = usePublicPageStore()

  async function show(routeSlug: string, picked: string | null): Promise<void> {
    if (picked && store.isBrandUnit(picked)) return store.load(picked)
    if (!store.isBrandUnit(routeSlug)) store.forgetBrand()
    await store.load(routeSlug)
    const brandSlug = store.slug === routeSlug ? store.page?.brandSlug : null
    if (!brandSlug) return
    await store.loadBrand(brandSlug)
    if (picked && picked !== routeSlug && store.isBrandUnit(picked)) await store.load(picked)
  }

  // Watched as one string: only a change of slug or of picked unit loads again (not any other query change).
  watch(
    () => {
      const slug = route.params.slug
      const picked = route.query[BRAND_UNIT_QUERY]
      return typeof slug === 'string' ? `${slug}\n${typeof picked === 'string' ? picked : ''}` : null
    },
    (key) => {
      if (key === null) return
      const [slug = '', picked = ''] = key.split('\n')
      void show(slug, picked || null)
    },
    { immediate: true },
  )

  return store
}

/**
 * Links inside a public page ("Agendar", "Sobre", back) that keep the visitor on the brand link with the picked unit
 * (/{brand}/agendar?unidade=…). Outside a brand link they are the plain /{slug}/… links.
 */
export function usePublicLinks() {
  const route = useRoute()

  return (subPath = ''): string => {
    const slug = typeof route.params.slug === 'string' ? route.params.slug : ''
    const picked = route.query[BRAND_UNIT_QUERY]
    const query = typeof picked === 'string' ? `?${BRAND_UNIT_QUERY}=${encodeURIComponent(picked)}` : ''
    return `/${slug}${subPath}${query}`
  }
}
