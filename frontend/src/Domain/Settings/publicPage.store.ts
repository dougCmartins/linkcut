import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchBookingOptions, fetchPublicPage } from './settings.api'
import { fetchBrand, type Brand } from '@/Domain/Tenant/brand.api'
import { DEFAULT_THEME } from './settings.defaults'
import type { PublicPage, ScheduleSettings } from './settings.model'
import { ApiError } from '@/http/apiError'
import { LoadStatus } from '@/types/loadStatus'

const VISITED_SLUG_KEY = 'linkcut.visited-slug'

/** The last barbershop page opened on this device: the client's login (/<slug>/login) goes back there. */
export function lastVisitedSlug(): string | null {
  try {
    return localStorage.getItem(VISITED_SLUG_KEY)
  } catch {
    return null
  }
}

function rememberVisitedSlug(slug: string): void {
  try {
    localStorage.setItem(VISITED_SLUG_KEY, slug)
  } catch {
    // Storage blocked: the client signs in on /conta/entrar instead.
  }
}

/** The barbershop a visitor is looking at (/:slug): bio link, portfolio, theme and booking options. */
export const usePublicPageStore = defineStore('publicPage', () => {
  const slug = ref<string | null>(null)
  const page = ref<PublicPage | null>(null)
  const bookingOptions = ref<ScheduleSettings | null>(null)
  const status = ref<LoadStatus>(LoadStatus.Idle)
  /** The brand whose link is open (/{brand}), with its units: the picker and "the other units" in the booking. */
  const brand = ref<Brand | null>(null)

  const theme = computed(() => page.value?.theme ?? DEFAULT_THEME)

  async function load(nextSlug: string): Promise<void> {
    const isCurrent = nextSlug === slug.value && (status.value === LoadStatus.Loading || status.value === LoadStatus.Ready)
    if (isCurrent) return
    slug.value = nextSlug
    page.value = null
    bookingOptions.value = null
    status.value = LoadStatus.Loading
    try {
      const [loadedPage, options] = await Promise.all([fetchPublicPage(nextSlug), fetchBookingOptions(nextSlug)])
      if (slug.value !== nextSlug) return
      page.value = loadedPage
      bookingOptions.value = options
      status.value = LoadStatus.Ready
      rememberVisitedSlug(nextSlug)
    } catch (caught) {
      if (slug.value !== nextSlug) return
      status.value = caught instanceof ApiError && caught.status === 404 ? LoadStatus.NotFound : LoadStatus.Failed
    }
  }

  function reload(): Promise<void> {
    const current = slug.value
    if (!current) return Promise.resolve()
    status.value = LoadStatus.Idle
    return load(current)
  }

  /** The units of the brand link; on failure the page simply shows without the picker. */
  async function loadBrand(brandSlug: string): Promise<void> {
    if (brand.value?.slug === brandSlug) return
    try {
      brand.value = await fetchBrand(brandSlug)
    } catch {
      brand.value = null
    }
  }

  /** Whether a slug is one of the units of the brand that is open (a picked unit must be one of them). */
  function isBrandUnit(unitSlug: string): boolean {
    return brand.value?.units.some((unit) => unit.slug === unitSlug) ?? false
  }

  function forgetBrand(): void {
    brand.value = null
  }

  return { slug, page, bookingOptions, status, theme, brand, load, reload, loadBrand, isBrandUnit, forgetBrand }
})
