<script setup lang="ts">
import { usePageMeta } from '@/composables/usePageMeta'
import LegalSections from './LegalSections.vue'
import PrivacyLinks from './PrivacyLinks.vue'
import { useLegalDocument, type LegalDocument } from './useLegalDocument'

/** /privacidade and /termos: the Privacy Policy (LGPD) and the Terms of Use, each as its own page. */
const props = defineProps<{ document: LegalDocument }>()
const legal = useLegalDocument(() => props.document)
usePageMeta(legal.title, legal.intro)
</script>

<template>
  <main v-if="legal.isLoaded.value" class="mx-auto w-full max-w-2xl px-5 pt-16 pb-16">
    <RouterLink to="/" class="text-sm font-semibold text-accent">← linkcut</RouterLink>
    <h1 class="mt-4 text-3xl leading-tight">{{ legal.title.value }}</h1>
    <p class="mt-1 text-xs text-muted">{{ legal.updated.value }}</p>
    <p class="mt-4 leading-relaxed text-muted">{{ legal.intro.value }}</p>
    <LegalSections :sections="legal.sections.value" id-prefix="legal" />
    <PrivacyLinks class="mt-12" />
  </main>
</template>
