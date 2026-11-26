import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchBookingOptions, fetchPublicPage } from './settings.api'
import { DEFAULT_THEME } from './settings.defaults'
import type { PublicPage, ScheduleSettings } from './settings.model'
import { fetchPublicStaff } from '@/Domain/Schedule/booking.api'
import type { PublicStaff } from '@/Domain/Schedule/booking.model'
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
  /** Active barbers of the unit: the booking shows "Escolha o barbeiro" from 2 on. */
  const staff = ref<PublicStaff[]>([])
  const status = ref<LoadStatus>(LoadStatus.Idle)

  const theme = computed(() => page.value?.theme ?? DEFAULT_THEME)

  async function load(nextSlug: string, options: { keepContent?: boolean } = {}): Promise<void> {
    const isCurrent = nextSlug === slug.value && (status.value === LoadStatus.Loading || status.value === LoadStatus.Ready)
    if (isCurrent) return
    slug.value = nextSlug
    if (!options.keepContent) {
      page.value = null
      bookingOptions.value = null
      staff.value = []
    }
    status.value = LoadStatus.Loading
    try {
      const [loadedPage, booking, members] = await Promise.all([
        fetchPublicPage(nextSlug),
        fetchBookingOptions(nextSlug),
        // Without the list the booking works as with one barber ("Qualquer um").
        fetchPublicStaff(nextSlug).catch(() => []),
      ])
      if (slug.value !== nextSlug) return
      page.value = loadedPage
      bookingOptions.value = booking
      staff.value = members
      status.value = LoadStatus.Ready
      rememberVisitedSlug(nextSlug)
    } catch (caught) {
      if (slug.value !== nextSlug) return
      if (!options.keepContent) {
        page.value = null
        bookingOptions.value = null
      }
      status.value = caught instanceof ApiError && caught.status === 404 ? LoadStatus.NotFound : LoadStatus.Failed
    }
  }

  /** Swap the unit on a brand page without blanking the current "Quem somos". */
  function loadUnit(nextSlug: string): Promise<void> {
    return load(nextSlug, { keepContent: true })
  }

  function reload(): Promise<void> {
    const current = slug.value
    if (!current) return Promise.resolve()
    status.value = LoadStatus.Idle
    return load(current)
  }

  return { slug, page, bookingOptions, staff, status, theme, load, loadUnit, reload }
})
