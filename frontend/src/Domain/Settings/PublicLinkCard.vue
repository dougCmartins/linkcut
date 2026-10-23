<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useTenantStore } from '@/Domain/Tenant/tenant.store'
import SettingsCard from './SettingsCard.vue'

/** The barbershop's public link, ready to copy (or share) and paste in the Instagram bio. */
const { t } = useI18n()
const tenantStore = useTenantStore()
const ShareSheet = defineAsyncComponent(() => import('@/components/ui/ShareSheet.vue'))

const copied = ref(false)
const isSharing = ref(false)
const path = computed(() => (tenantStore.tenant ? `/${tenantStore.tenant.slug}` : ''))
const url = computed(() => (path.value ? `${window.location.origin}${path.value}` : ''))

async function copy(): Promise<void> {
  try {
    await navigator.clipboard.writeText(url.value)
    copied.value = true
  } catch {
    copied.value = false
  }
}

onMounted(() => void tenantStore.ensureLoaded())
</script>

<template>
  <SettingsCard :title="t('settings.publicLink.title')" :description="t('settings.publicLink.description')">
    <p class="rounded-card bg-raised px-4 py-3 font-display font-bold [overflow-wrap:anywhere] ring-1 ring-line" data-testid="public-link">
      {{ url || t('settings.publicLink.loading') }}
    </p>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
      <BaseButton variant="primary" elevated block :disabled="!url" @click="copy">
        {{ copied ? t('settings.publicLink.copied') : t('settings.publicLink.copy') }}
      </BaseButton>
      <BaseButton v-if="path" variant="secondary" block :to="path">{{ t('settings.publicLink.view') }}</BaseButton>
    </div>
    <BaseButton v-if="url" variant="ghost" block @click="isSharing = true">{{ t('settings.publicLink.share') }}</BaseButton>
    <ShareSheet v-if="isSharing" :url="url" :name="tenantStore.tenant?.name ?? ''" @close="isSharing = false" />
    <p class="text-xs text-muted" role="status">{{ copied ? t('settings.publicLink.pasteHint') : t('settings.publicLink.hint') }}</p>
  </SettingsCard>
</template>
