import { render } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'
import { routes } from '@/router'
import { i18n } from '@/i18n'

type RenderArgs = Parameters<typeof render>

/** Renders a component with the app plugins (i18n, a fresh Pinia and an in-memory router). */
export function renderWithPlugins(component: RenderArgs[0], options: NonNullable<RenderArgs[1]> = {}) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const router = createRouter({ history: createMemoryHistory(), routes })

  return render(component, {
    ...options,
    global: { ...options.global, plugins: [pinia, router, i18n] },
  })
}
