<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseCard from '@/components/ui/BaseCard.vue'
import HighlightChips from './HighlightChips.vue'
import { splitParagraphs, type PublicPortfolio } from '@/Domain/Settings/settings.model'

const props = defineProps<{ name: string; portfolio: Pick<PublicPortfolio, 'quote' | 'story' | 'highlights'> }>()
const { t } = useI18n()

const paragraphs = computed(() => splitParagraphs(props.portfolio.story))
const highlights = computed(() => props.portfolio.highlights.filter((highlight) => highlight.value.trim()))
</script>

<template>
  <BaseCard floating class="relative px-6 pt-7 pb-6">
    <span class="block h-0.5 w-10 rounded-full bg-accent" aria-hidden="true" />
    <p class="mt-4 text-[11px] font-bold tracking-[0.22em] text-accent uppercase">{{ t('portfolio.eyebrow') }}</p>
    <h1 class="mt-2 text-[28px] leading-[1.05] break-words">
      {{ t('portfolio.greeting') }}
      <span class="text-accent">{{ name }}</span>
    </h1>
    <blockquote
      v-if="portfolio.quote"
      class="mt-4 border-l-2 border-accent/60 pl-4 text-[15px] leading-relaxed text-ink/90 italic break-words"
    >
      “{{ portfolio.quote }}”
    </blockquote>
    <div v-if="paragraphs.length" class="mt-4 space-y-3 text-[15px] leading-relaxed text-muted">
      <p v-for="(paragraph, index) in paragraphs" :key="index" class="whitespace-pre-line break-words">{{ paragraph }}</p>
    </div>
    <HighlightChips v-if="highlights.length" :highlights="highlights" class="mt-6" />
  </BaseCard>
</template>
