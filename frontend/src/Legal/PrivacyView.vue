<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { i18n } from '@/i18n'
import { usePageMeta } from '@/composables/usePageMeta'
import PrivacyLinks from './PrivacyLinks.vue'

/**
 * /privacidade: the privacy policy (LGPD). The controller's name, document and contact e-mail come from the build
 * (VITE_PRIVACY_*), so they are filled in without touching the text. It is a template for legal review.
 */
const { t, tm, rt } = useI18n()
/** The policy text is downloaded only on this page (keeps every other page light). */
const isLoaded = ref(false)
onMounted(async () => {
  const policy = await import('@/i18n/privacyPolicy.pt-BR')
  i18n.global.mergeLocaleMessage('pt-BR', policy.default)
  isLoaded.value = true
})
usePageMeta(
  () => (isLoaded.value ? t('privacy.page.title') : ''),
  () => (isLoaded.value ? t('privacy.page.intro') : ''),
)

const SECTIONS = ['controller', 'data', 'purposes', 'sharing', 'retention', 'storage', 'rights', 'changes'] as const
const contact = {
  controller: import.meta.env.VITE_PRIVACY_CONTROLLER || '[razão social]',
  document: import.meta.env.VITE_PRIVACY_DOCUMENT || '[CNPJ]',
  email: import.meta.env.VITE_PRIVACY_EMAIL || '[e-mail do encarregado]',
}
const sections = computed(() =>
  !isLoaded.value
    ? []
    : SECTIONS.map((key) => ({
        key,
        title: t(`privacy.page.sections.${key}.title`),
        items: (tm(`privacy.page.sections.${key}.items`) as unknown[]).map((item) => rt(item as Parameters<typeof rt>[0], contact)),
      })),
)
</script>

<template>
  <main v-if="isLoaded" class="mx-auto w-full max-w-2xl px-5 pt-16 pb-16">
    <RouterLink to="/" class="text-sm font-semibold text-accent">← linkcut</RouterLink>
    <h1 class="mt-4 text-3xl leading-tight">{{ t('privacy.page.title') }}</h1>
    <p class="mt-1 text-xs text-muted">{{ t('privacy.page.updated') }}</p>
    <p class="mt-4 leading-relaxed text-muted">{{ t('privacy.page.intro') }}</p>

    <section v-for="section in sections" :key="section.key" class="mt-8" :aria-labelledby="`privacy-${section.key}`">
      <h2 :id="`privacy-${section.key}`" class="text-lg">{{ section.title }}</h2>
      <ul class="mt-3 grid list-disc gap-2 pl-5 text-sm leading-relaxed">
        <li v-for="(item, index) in section.items" :key="index">{{ item }}</li>
      </ul>
    </section>

    <PrivacyLinks class="mt-12" />
  </main>
</template>
