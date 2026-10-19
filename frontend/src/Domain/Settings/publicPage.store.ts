import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchBookingOptions, fetchPublicPage } from './settings.api'
import { DEFAULT_THEME } from './settings.defaults'
import type { PublicPage, ScheduleSettings } from './settings.model'
import { ApiError } from '@/http/apiError'
import { LoadStatus } from '@/types/loadStatus'

/** The barbershop a visitor is looking at (/:slug): bio link, portfolio, theme and booking options. */
export const usePublicPageStore = defineStore('publicPage', () => {
  const slug = ref<string | null>(null)
  const page = ref<PublicPage | null>(null)
  const bookingOptions = ref<ScheduleSettings | null>(null)
  const status = ref<LoadStatus>(LoadStatus.Idle)

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

  return { slug, page, bookingOptions, status, theme, load, reload }
})
