<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { usePublicPage } from '@/Domain/Settings/usePublicPage'
import {
  CLIENT_APPOINTMENTS_ROUTE,
  CLIENT_BOOK_ROUTE,
  CLIENT_PRODUCTS_ROUTE,
  CLIENT_PROFILE_ROUTE,
  clientLoginLocation,
} from './client.guard'
import BarbershopLogo from './BarbershopLogo.vue'
import { useClientStore } from './client.store'

/**
 * Frame of the client area. Inside a barbershop (/<slug>/account…): its photo and name, "Agendar", "Sair" and the
 * menu (Horários · Produtos · Minha conta; Produtos only with an active product), at the bottom of the screen on a phone. Without a barbershop (/account): only the
 * greeting and "Sair".
 */
defineProps<{ title: string }>()
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const clientStore = useClientStore()
// Loads the barbershop of the address: its theme (App.vue), its name and photo, and the booking options.
const publicPage = usePublicPage()

const slug = computed(() => (typeof route.params.slug === 'string' ? route.params.slug : null))
const barbershop = computed(() => (slug.value ? (publicPage.page?.profile ?? null) : null))
const firstName = computed(() => clientStore.client?.name.trim().split(' ')[0] ?? '')

const LINKS = [
  { name: CLIENT_APPOINTMENTS_ROUTE, key: 'appointments', icon: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z' },
  { name: CLIENT_PRODUCTS_ROUTE, key: 'products', icon: 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0' },
  { name: CLIENT_PROFILE_ROUTE, key: 'profile', icon: 'M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10z' },
] as const

// "Produtos" only when this barbershop has an active product (also kept while the client is on that tab).
const hasProducts = computed(() =>
  clientStore.appointments.some((item) => item.barbershop.slug === slug.value && item.hasProducts),
)
const links = computed(() =>
  LINKS.filter((link) => link.name !== CLIENT_PRODUCTS_ROUTE || hasProducts.value || route.name === CLIENT_PRODUCTS_ROUTE),
)

onMounted(() => {
  if (slug.value) void clientStore.ensureAppointments().catch(() => undefined)
})

/** "Horários" stays lit while the booking is open on top of it. */
function isActive(name: string): boolean {
  return route.name === name || (name === CLIENT_APPOINTMENTS_ROUTE && route.name === CLIENT_BOOK_ROUTE)
}

async function logout(): Promise<void> {
  await clientStore.logout()
  await router.replace(clientLoginLocation({}, slug.value ?? undefined))
}
</script>

<template>
  <main class="mx-auto w-full max-w-md px-4 pt-6" :class="slug ? 'pb-28 sm:pb-12' : 'pb-12'">
    <header class="flex items-center gap-3">
      <RouterLink
        v-if="slug"
        :to="{ name: 'bio-link', params: { slug } }"
        :aria-label="t('client.menu.page')"
        :title="t('client.menu.page')"
        class="shrink-0 rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      >
        <BarbershopLogo :name="barbershop?.name ?? ''" :src="barbershop?.avatarUrl || null" size="md" />
      </RouterLink>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm text-muted">
          <span v-if="barbershop" class="font-semibold text-ink">{{ barbershop.name }} · </span>
          {{ t('client.appointments.greeting', { name: firstName }) }}
        </p>
        <h1 class="text-2xl leading-tight break-words">{{ title }}</h1>
      </div>
      <button
        type="button"
        :aria-label="t('auth.logout')"
        :title="t('auth.logout')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:text-danger focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        @click="logout"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
        </svg>
      </button>
    </header>

    <template v-if="slug">
      <RouterLink
        :to="{ name: CLIENT_BOOK_ROUTE, params: { slug } }"
        class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-button bg-accent font-display text-[15px] font-bold text-on-accent shadow-soft transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M12 5v14M5 12h14" />
        </svg>
        {{ t('client.menu.book') }}
      </RouterLink>

      <!-- Phone: fixed at the bottom, like an app. Larger screens: tabs under the header. -->
      <nav
        :aria-label="t('client.menu.label')"
        class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur sm:static sm:mt-5 sm:rounded-full sm:border-0 sm:bg-surface sm:p-1 sm:ring-1 sm:ring-line sm:shadow-soft"
      >
        <ul class="mx-auto grid max-w-md gap-1" :class="links.length === 3 ? 'grid-cols-3' : 'grid-cols-2'">
          <li v-for="link in links" :key="link.name">
            <RouterLink
              :to="{ name: link.name, params: { slug } }"
              :aria-current="isActive(link.name) ? 'page' : undefined"
              class="flex h-14 flex-col items-center justify-center gap-0.5 font-display text-xs font-extrabold tracking-tight transition focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-accent sm:h-11 sm:flex-row sm:gap-2 sm:rounded-full sm:text-sm"
              :class="isActive(link.name) ? 'text-accent sm:bg-accent sm:text-on-accent' : 'text-muted hover:text-ink'"
            >
              <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path :d="link.icon" />
              </svg>
              {{ t(`client.menu.${link.key}`) }}
            </RouterLink>
          </li>
        </ul>
      </nav>
    </template>

    <div class="mt-6">
      <slot />
    </div>
  </main>
</template>
