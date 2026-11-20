<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { BRAND_UNIT_QUERY } from '@/Domain/Settings/usePublicPage'
import { formatAddress } from '@/utils/address'

/**
 * In the booking opened from a brand link: the other units with their addresses, to book at another one instead
 * (docs/decisoes/0001 P2). The booking always goes to the unit shown at the top.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const store = usePublicPageStore()

const others = computed(() => {
  const brand = store.brand
  if (!brand || route.params.slug !== brand.slug) return []
  return brand.units.filter((unit) => unit.slug !== store.slug)
})
const current = computed(() => store.brand?.units.find((unit) => unit.slug === store.slug) ?? null)

function bookAt(slug: string): void {
  const query = { ...route.query }
  if (slug === store.brand?.slug) delete query[BRAND_UNIT_QUERY]
  else query[BRAND_UNIT_QUERY] = slug
  void router.replace({ query })
}
</script>

<template>
  <div v-if="others.length && current" class="mx-5 mb-2 rounded-card bg-surface p-3 text-sm ring-1 ring-line">
    <p class="font-bold">{{ current.name }}</p>
    <p v-if="current.address" class="text-xs text-muted">{{ formatAddress(current.address) }}</p>
    <details class="mt-2">
      <summary class="cursor-pointer text-xs font-semibold text-accent">{{ t('brand.otherUnits') }}</summary>
      <ul class="mt-2 grid gap-2">
        <li v-for="unit in others" :key="unit.slug" class="flex items-center justify-between gap-3">
          <span class="min-w-0">
            <span class="block font-semibold">{{ unit.name }}</span>
            <span v-if="unit.address" class="block text-xs text-muted">{{ formatAddress(unit.address) }}</span>
          </span>
          <button type="button" class="shrink-0 text-xs font-bold text-accent" @click="bookAt(unit.slug)">
            {{ t('brand.bookHere') }}
          </button>
        </li>
      </ul>
    </details>
  </div>
</template>
