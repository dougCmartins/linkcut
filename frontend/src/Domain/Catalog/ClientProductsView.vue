<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import SmartImage from '@/components/ui/SmartImage.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import ClientLayout from '@/Domain/Client/ClientLayout.vue'
import { useClientStore } from '@/Domain/Client/client.store'
import { buildWhatsappUrl, onlyDigits } from '@/utils/phone'
import { formatBRL } from '@/utils/currency'
import { fetchClientProducts } from './catalog.api'
import type { ShowcaseProduct } from './catalog.model'

/**
 * /conta/barbearias/:slug/produtos: active products of one barbershop the client already booked.
 * The phone's back button returns to Minhas barbearias.
 */
const { t } = useI18n()
const route = useRoute()
const clientStore = useClientStore()
const { messageKey } = useApiErrors()

const slug = computed(() => (typeof route.params.slug === 'string' ? route.params.slug : ''))
const products = ref<ShowcaseProduct[]>([])
const isLoading = ref(true)
const error = ref('')

const barbershop = computed(() => clientStore.appointments.find((item) => item.barbershop.slug === slug.value)?.barbershop ?? null)
const hasWhatsapp = computed(() => Boolean(barbershop.value?.whatsappUrl))

function interestHref(name: string): string {
  return buildWhatsappUrl(onlyDigits(barbershop.value?.whatsappUrl ?? ''), t('catalog.interestMessage', { name }))
}

watch(slug, async (value) => {
  if (!value) return
  isLoading.value = true
  error.value = ''
  try {
    await clientStore.ensureAppointments()
    products.value = await fetchClientProducts(value)
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    isLoading.value = false
  }
}, { immediate: true })
</script>

<template>
  <ClientLayout :title="barbershop?.name || t('catalog.title')">
    <RouterLink :to="{ name: 'client-barbershops' }" class="text-sm font-bold text-accent">
      ← {{ t('client.barbershops.title') }}
    </RouterLink>

    <p v-if="isLoading" class="mt-4 text-sm text-muted" role="status">{{ t('catalog.loading') }}</p>
    <p v-else-if="error" class="mt-4 text-sm font-semibold text-danger" role="alert">{{ error }}</p>
    <p v-else-if="products.length === 0" class="mt-4 text-sm text-muted">{{ t('catalog.empty') }}</p>
    <ul v-else class="mt-4 flex flex-col gap-4" :aria-label="t('catalog.sectionTitle')">
      <li v-for="product in products" :key="product.id">
        <BaseCard class="overflow-hidden">
          <div v-if="product.imageUrl" class="aspect-square overflow-hidden">
            <SmartImage :src="product.imageUrl" :alt="product.name" lazy class="size-full object-cover" />
          </div>
          <div class="grid gap-2 p-4">
            <h2 class="text-lg leading-tight break-words">{{ product.name }}</h2>
            <p class="text-sm font-semibold text-accent">{{ formatBRL(product.priceInCents) }}</p>
            <p v-if="product.description" class="text-sm leading-relaxed text-muted break-words">{{ product.description }}</p>
            <BaseButton v-if="hasWhatsapp" class="mt-2" variant="secondary" block :href="interestHref(product.name)">
              {{ t('catalog.interest') }}
            </BaseButton>
          </div>
        </BaseCard>
      </li>
    </ul>
  </ClientLayout>
</template>
