<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { i18n } from '@/i18n'
import { usePageMeta } from '@/composables/usePageMeta'
import PrivacyLinks from './PrivacyLinks.vue'

/**
 * /privacidade and /termos: the Privacy Policy (LGPD) and the Terms of Use. The text of each is downloaded only on
 * its page. The controller's name, document and contact e-mail come from the build (VITE_PRIVACY_*), so they are
 * filled in without touching the texts. Both are templates for legal review.
 */
const props = defineProps<{ document: 'privacy' | 'terms' }>()
const { t, tm, rt } = useI18n()

const LOADERS = {
  privacy: () => import('@/i18n/privacyPolicy.pt-BR'),
  terms: () => import('@/i18n/termsOfUse.pt-BR'),
} as const

const isLoaded = ref(false)
onMounted(async () => {
  const messages = await LOADERS[props.document]()
  i18n.global.mergeLocaleMessage('pt-BR', messages.default)
  isLoaded.value = true
})
const base = computed(() => `${props.document}.page`)
usePageMeta(
  () => (isLoaded.value ? t(`${base.value}.title`) : ''),
  () => (isLoaded.value ? t(`${base.value}.intro`) : ''),
)

const contact = {
  controller: import.meta.env.VITE_PRIVACY_CONTROLLER || '[razão social]',
  document: import.meta.env.VITE_PRIVACY_DOCUMENT || '[CNPJ]',
  email: import.meta.env.VITE_PRIVACY_EMAIL || '[e-mail do encarregado]',
}
const sections = computed(() => {
  if (!isLoaded.value) return []
  const all = tm(`${base.value}.sections`) as Record<string, unknown>
  return Object.keys(all).map((key) => ({
    key,
    title: t(`${base.value}.sections.${key}.title`),
    items: (tm(`${base.value}.sections.${key}.items`) as unknown[]).map((item) => rt(item as Parameters<typeof rt>[0], contact)),
  }))
})
</script>

<template>
  <main v-if="isLoaded" class="mx-auto w-full max-w-2xl px-5 pt-16 pb-16">
    <RouterLink to="/" class="text-sm font-semibold text-accent">← linkcut</RouterLink>
    <h1 class="mt-4 text-3xl leading-tight">{{ t(`${base}.title`) }}</h1>
    <p class="mt-1 text-xs text-muted">{{ t(`${base}.updated`) }}</p>
    <p class="mt-4 leading-relaxed text-muted">{{ t(`${base}.intro`) }}</p>

    <section v-for="section in sections" :key="section.key" class="mt-8" :aria-labelledby="`legal-${section.key}`">
      <h2 :id="`legal-${section.key}`" class="text-lg">{{ section.title }}</h2>
      <ul class="mt-3 grid list-disc gap-2 pl-5 text-sm leading-relaxed">
        <li v-for="(item, index) in section.items" :key="index">{{ item }}</li>
      </ul>
    </section>

    <PrivacyLinks class="mt-12" />
  </main>
</template>
