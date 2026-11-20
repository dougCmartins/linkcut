import { computed, ref, watch, type Ref } from 'vue'
import { fetchPublicBrandOrNull, type BrandUnit, type PublicBrand } from '@/Domain/Tenant/brand.api'

/**
 * On a public /:slug page, loads the brand units when the slug is an organization with 2+ units.
 * Selecting a unit keeps the URL; the caller reloads the public page for the unit slug.
 */
export function useBrandUnitSelect(routeSlug: Ref<string | null> | { value: string | null }, onSelectUnit: (unitSlug: string) => void) {
  const brand = ref<PublicBrand | null>(null)
  const selectedUnitSlug = ref('')

  const showSelect = computed(() => (brand.value?.units.length ?? 0) >= 2)
  const unitOptions = computed(() =>
    (brand.value?.units ?? []).map((unit) => ({ value: unit.slug, label: unit.name })),
  )
  const otherUnits = computed((): BrandUnit[] =>
    (brand.value?.units ?? []).filter((unit) => unit.slug !== selectedUnitSlug.value),
  )

  watch(
    routeSlug,
    async (slug) => {
      brand.value = null
      selectedUnitSlug.value = ''
      if (!slug) return
      const loaded = await fetchPublicBrandOrNull(slug)
      if (!loaded || loaded.units.length < 2) return
      brand.value = loaded
      const main = loaded.units.find((unit) => unit.isMain) ?? loaded.units[0]
      if (!main) return
      selectedUnitSlug.value = main.slug
      if (main.slug !== slug) onSelectUnit(main.slug)
    },
    { immediate: true },
  )

  function selectUnit(unitSlug: string): void {
    if (!unitSlug || unitSlug === selectedUnitSlug.value) return
    selectedUnitSlug.value = unitSlug
    onSelectUnit(unitSlug)
  }

  return { brand, selectedUnitSlug, showSelect, unitOptions, otherUnits, selectUnit }
}
