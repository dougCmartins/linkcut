import { onBeforeUnmount, onMounted } from 'vue'

/** Message the service worker posts to the open tabs when a push arrives (public/sw.js). */
export const PUSH_MESSAGE_TYPE = 'linkcut:push'

/** How often an open, visible screen asks the API again. Cheap: a few indexed queries. */
export const LIVE_REFRESH_INTERVAL_MS = 30_000

/** A tab coming back to the front does not refresh again if it just did. */
const MIN_GAP_MS = 5_000

/**
 * Keeps a screen up to date without F5:
 * - right away when a push arrives to this browser (e.g. "Novo pedido de agendamento");
 * - when the tab or the installed app comes back to the front;
 * - every 30 seconds while it is visible (covers browsers without push).
 * `isBusy` skips a round while the user is in the middle of an action, so an older answer never overwrites it.
 */
export function useLiveRefresh(refresh: () => Promise<void>, options: { isBusy?: () => boolean; intervalMs?: number } = {}) {
  const intervalMs = options.intervalMs ?? LIVE_REFRESH_INTERVAL_MS
  let timer: ReturnType<typeof setInterval> | undefined
  let lastRun = Date.now()
  let running = false

  async function run(force = false): Promise<void> {
    if (running || options.isBusy?.()) return
    if (!force && Date.now() - lastRun < MIN_GAP_MS) return
    running = true
    lastRun = Date.now()
    try {
      await refresh()
    } finally {
      running = false
    }
  }

  const onVisible = () => {
    if (document.visibilityState === 'visible') void run()
  }
  const onTick = () => {
    if (document.visibilityState === 'visible') void run(true)
  }
  const onWorkerMessage = (event: MessageEvent) => {
    if ((event.data as { type?: string } | null)?.type === PUSH_MESSAGE_TYPE) void run(true)
  }

  onMounted(() => {
    timer = setInterval(onTick, intervalMs)
    document.addEventListener('visibilitychange', onVisible)
    window.addEventListener('focus', onVisible)
    navigator.serviceWorker?.addEventListener('message', onWorkerMessage)
  })

  onBeforeUnmount(() => {
    clearInterval(timer)
    document.removeEventListener('visibilitychange', onVisible)
    window.removeEventListener('focus', onVisible)
    navigator.serviceWorker?.removeEventListener('message', onWorkerMessage)
  })

  return { refreshNow: () => run(true) }
}
