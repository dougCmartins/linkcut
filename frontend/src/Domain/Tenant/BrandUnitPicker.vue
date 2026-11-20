<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import SelectField from '@/components/ui/SelectField.vue'
import { usePublicPageStore } from '@/Domain/Settings/publicPage.store'
import { BRAND_UNIT_QUERY } from '@/Domain/Settings/usePublicPage'
import { formatAddress } from '@/utils/address'

/**
 * The branch picker of a brand link (docs/decisoes/0001 P2): picking a unit shows its page in place (?unidade=),
 * without leaving or reloading the page. Shown only on the brand link itself, never on a branch's own link.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const store = usePublicPageStore()

const isBrandLink = computed(() => store.brand !== null && store.brand.units.length > 1 && route.params.slug === store.brand.slug)
const options = computed(() =>
  (store.brand?.units ?? []).map((unit) => ({
    value: unit.slug,
    label: unit.address ? `${unit.name} · ${unit.address.district}` : unit.name,
  })),
)
const pickedUnit = computed(() => store.brand?.units.find((unit) => unit.slug === store.slug) ?? null)

const picked = computed({
  get: () => store.slug ?? '',
  set: (slug: string) => {
    const query = { ...route.query }
    if (slug === store.brand?.slug) delete query[BRAND_UNIT_QUERY]
    else query[BRAND_UNIT_QUERY] = slug
    void router.replace({ query })
  },
})
</script>

<template>
  <div v-if="isBrandLink" class="w-full">
    <SelectField v-model="picked" :label="t('brand.unit')" :options="options" />
    <p v-if="pickedUnit?.address" class="mt-1.5 pl-2 text-xs text-muted">{{ formatAddress(pickedUnit.address) }}</p>
  </div>
</template>
