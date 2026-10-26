<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { clientLoginLocation } from './client.guard'
import { useClientStore } from './client.store'

/** Frame of the client area: greeting, "Sair" and the menu (Meus horários · Barbearias · Minha conta). */
defineProps<{ title: string }>()
const { t } = useI18n()
const router = useRouter()
const clientStore = useClientStore()

const firstName = computed(() => clientStore.client?.name.trim().split(' ')[0] ?? '')
const LINKS = [
  { name: 'client-appointments', key: 'appointments' },
  { name: 'client-barbershops', key: 'barbershops' },
  { name: 'client-profile', key: 'profile' },
] as const

async function logout(): Promise<void> {
  await clientStore.logout()
  await router.replace(clientLoginLocation())
}
</script>

<template>
  <main class="mx-auto w-full max-w-md px-4 pt-6 pb-12">
    <header class="flex items-center gap-3">
      <div class="min-w-0 flex-1">
        <p class="text-sm text-muted">{{ t('client.appointments.greeting', { name: firstName }) }}</p>
        <h1 class="text-2xl leading-tight break-words">{{ title }}</h1>
      </div>
      <button
        type="button"
        :aria-label="t('auth.logout')"
        :title="t('auth.logout')"
        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:text-danger"
        @click="logout"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
        </svg>
      </button>
    </header>

    <nav :aria-label="t('client.menu.label')" class="mt-5 grid grid-cols-3 gap-1 rounded-full bg-surface p-1 ring-1 ring-line shadow-soft">
      <RouterLink
        v-for="link in LINKS"
        :key="link.name"
        :to="{ name: link.name }"
        class="flex h-10 min-w-0 items-center justify-center rounded-full px-2 text-center font-display text-xs font-extrabold tracking-tight text-muted transition hover:text-ink sm:text-sm"
        exact-active-class="!bg-accent !text-on-accent shadow-soft"
      >
        <span class="truncate">{{ t(`client.menu.${link.key}`) }}</span>
      </RouterLink>
    </nav>

    <div class="mt-6">
      <slot />
    </div>
  </main>
</template>
