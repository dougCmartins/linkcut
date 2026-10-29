<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import ImageUploader from '@/components/ui/ImageUploader.vue'
import TextArea from '@/components/ui/TextArea.vue'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { useImageUpload } from '@/Domain/Settings/useImageUpload'
import type { ImageRef } from '@/Domain/Settings/settings.model'
import { ProductLimits } from '@/types/productLimits'
import { formatBRL, formatMoneyInput, parseMoneyInput } from '@/utils/currency'
import { createProduct, deleteProduct, fetchProducts, reorderProducts, updateProduct } from './catalog.api'
import type { CatalogProduct, CatalogProductInput } from './catalog.model'

const { t } = useI18n()
const { messageKey } = useApiErrors()
const { upload, isBusy, errorFor: uploadErrorFor } = useImageUpload()

const products = ref<CatalogProduct[]>([])
const loading = ref(true)
const error = ref('')
const formOpen = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const name = ref('')
const price = ref('')
const description = ref('')
const isActive = ref(true)
const image = ref<ImageRef | null>(null)
const fieldErrors = ref<Record<string, string>>({})

const canAdd = computed(() => products.value.length < ProductLimits.MaxCount)
const formTitle = computed(() => (editingId.value === null ? t('catalog.add') : t('catalog.editTitle')))

function clearForm(): void {
  formOpen.value = false
  editingId.value = null
  name.value = ''
  price.value = ''
  description.value = ''
  isActive.value = true
  image.value = null
  fieldErrors.value = {}
}

function startCreate(): void {
  if (!canAdd.value) return
  clearForm()
  formOpen.value = true
}

function startEdit(product: CatalogProduct): void {
  editingId.value = product.id
  name.value = product.name
  price.value = formatMoneyInput(product.priceInCents)
  description.value = product.description
  isActive.value = product.isActive
  image.value = product.image
  fieldErrors.value = {}
  formOpen.value = true
}

function inputFromForm(): CatalogProductInput | null {
  const errors: Record<string, string> = {}
  const trimmedName = name.value.trim()
  const priceInCents = parseMoneyInput(price.value)
  if (!trimmedName) errors.name = t('catalog.nameRequired')
  if (
    priceInCents === null ||
    priceInCents < ProductLimits.MinPriceInCents ||
    priceInCents > ProductLimits.MaxPriceInCents
  ) {
    errors.price = t('catalog.priceInvalid')
  }
  fieldErrors.value = errors
  if (Object.keys(errors).length > 0 || priceInCents === null) return null
  return {
    name: trimmedName,
    priceInCents,
    description: description.value,
    imageId: image.value?.id ?? null,
    isActive: isActive.value,
  }
}

async function load(showSkeleton = false): Promise<void> {
  if (showSkeleton) loading.value = true
  error.value = ''
  try {
    products.value = await fetchProducts()
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    loading.value = false
  }
}

async function save(): Promise<void> {
  const input = inputFromForm()
  if (!input || saving.value) return
  saving.value = true
  error.value = ''
  try {
    if (editingId.value === null) await createProduct(input)
    else await updateProduct(editingId.value, input)
    clearForm()
    await load()
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    saving.value = false
  }
}

function replace(updated: CatalogProduct): void {
  products.value = products.value.map((product) => (product.id === updated.id ? updated : product))
  if (editingId.value === updated.id) isActive.value = updated.isActive
}

async function setActive(product: CatalogProduct, active: boolean): Promise<void> {
  const previous = product.isActive
  product.isActive = active
  error.value = ''
  try {
    replace(
      await updateProduct(product.id, {
        name: product.name,
        priceInCents: product.priceInCents,
        description: product.description,
        imageId: product.image?.id ?? null,
        isActive: active,
      }),
    )
  } catch (caught) {
    product.isActive = previous
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

async function move(index: number, direction: -1 | 1): Promise<void> {
  const target = index + direction
  if (target < 0 || target >= products.value.length) return
  const previous = products.value
  const next = previous.slice()
  const [moved] = next.splice(index, 1)
  if (!moved) return
  next.splice(target, 0, moved)
  products.value = next
  error.value = ''
  try {
    products.value = await reorderProducts(next.map((product) => product.id))
  } catch (caught) {
    products.value = previous
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

async function remove(product: CatalogProduct): Promise<void> {
  if (!window.confirm(t('catalog.confirmDelete'))) return
  error.value = ''
  try {
    await deleteProduct(product.id)
    products.value = products.value.filter((item) => item.id !== product.id)
    if (editingId.value === product.id) clearForm()
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

function onPhoto(file: File): void {
  const previous = image.value
  void upload('product-photo', file, (next) => (image.value = next), () => (image.value = previous))
}

onMounted(() => void load(true))
</script>

<template>
  <div class="grid gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="text-lg">{{ t('catalog.title') }}</h2>
      <BaseButton type="button" variant="secondary" :disabled="!canAdd" @click="startCreate">{{ t('catalog.add') }}</BaseButton>
    </div>
    <p v-if="!canAdd" class="text-sm text-muted" role="status">{{ t('catalog.limitReached', { count: ProductLimits.MaxCount }) }}</p>
    <p v-if="error" class="text-sm font-semibold text-danger" role="alert">{{ error }}</p>

    <BaseCard v-if="formOpen" class="grid gap-4 p-4">
      <h3 class="text-base">{{ formTitle }}</h3>
      <TextField v-model="name" :label="t('catalog.name')" :maxlength="ProductLimits.NameMaxLength" :error="fieldErrors.name" />
      <TextField
        v-model="price"
        :label="t('catalog.price')"
        inputmode="decimal"
        :error="fieldErrors.price"
        :placeholder="t('catalog.pricePlaceholder')"
      />
      <TextArea v-model="description" :label="t('catalog.description')" :maxlength="ProductLimits.DescriptionMaxLength" :rows="3" />
      <ToggleSwitch v-model="isActive" :label="t('catalog.active')" />
      <ImageUploader
        :label="t('catalog.photo')"
        shape="square"
        :src="image?.url ?? ''"
        :alt="name"
        :is-busy="isBusy('product-photo')"
        :error="uploadErrorFor('product-photo')"
        @select="onPhoto"
        @remove="image = null"
      />
      <div class="flex flex-wrap gap-3">
        <BaseButton type="button" :disabled="saving" @click="save">{{ saving ? t('catalog.saving') : t('catalog.save') }}</BaseButton>
        <BaseButton type="button" variant="ghost" @click="clearForm">{{ t('catalog.cancel') }}</BaseButton>
      </div>
    </BaseCard>

    <div v-if="loading" role="status" :aria-label="t('catalog.loading')" class="grid gap-3">
      <div class="h-28 animate-pulse rounded-card bg-surface ring-1 ring-line" />
      <div class="h-28 animate-pulse rounded-card bg-surface ring-1 ring-line" />
    </div>
    <p v-else-if="products.length === 0" class="text-sm text-muted">{{ t('catalog.empty') }}</p>
    <ul v-else class="grid gap-3">
      <li v-for="(product, index) in products" :key="product.id">
        <BaseCard class="grid gap-3 p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate text-base font-semibold">{{ product.name }}</p>
              <p class="text-sm font-semibold text-accent">{{ formatBRL(product.priceInCents) }}</p>
              <p v-if="product.description" class="mt-1 text-sm text-muted">{{ product.description }}</p>
            </div>
            <ToggleSwitch :model-value="product.isActive" :label="t('catalog.active')" @update:model-value="setActive(product, $event)" />
          </div>
          <div class="flex flex-wrap gap-3">
            <button type="button" class="text-sm font-semibold text-accent" @click="startEdit(product)">
              {{ t('catalog.edit', { name: product.name }) }}
            </button>
            <button
              type="button"
              class="text-sm font-semibold text-muted hover:text-ink disabled:opacity-40"
              :disabled="index === 0"
              :aria-label="t('catalog.moveUp', { name: product.name })"
              @click="move(index, -1)"
            >
              {{ t('catalog.moveUp', { name: product.name }) }}
            </button>
            <button
              type="button"
              class="text-sm font-semibold text-muted hover:text-ink disabled:opacity-40"
              :disabled="index === products.length - 1"
              :aria-label="t('catalog.moveDown', { name: product.name })"
              @click="move(index, 1)"
            >
              {{ t('catalog.moveDown', { name: product.name }) }}
            </button>
            <button
              type="button"
              class="text-sm font-semibold text-danger"
              :aria-label="t('catalog.remove', { name: product.name })"
              @click="remove(product)"
            >
              {{ t('catalog.remove', { name: product.name }) }}
            </button>
          </div>
        </BaseCard>
      </li>
    </ul>
  </div>
</template>
