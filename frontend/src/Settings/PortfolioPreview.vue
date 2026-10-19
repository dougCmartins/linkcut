<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SmartImage from '@/components/ui/SmartImage.vue'
import { splitParagraphs, type PortfolioSettings } from './settings.model'

const props = defineProps<{
  portfolio: PortfolioSettings
  name: string
  coverUrl: string
  photoUrls: string[]
}>()
const { t } = useI18n()

const paragraphs = computed(() => splitParagraphs(props.portfolio.story))
const highlights = computed(() => props.portfolio.highlights.filter((highlight) => highlight.value.trim()))
</script>

<template>
  <div class="pb-6">
    <div class="relative h-36 overflow-hidden">
      <SmartImage :src="coverUrl" alt="" class="size-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-b from-transparent to-page" />
    </div>
    <div class="relative -mt-12 px-3">
      <div class="rounded-card bg-surface p-4 ring-1 ring-line shadow-float">
        <span class="block h-0.5 w-8 rounded-full bg-accent" />
        <p class="mt-3 font-display text-base leading-tight font-extrabold tracking-tight break-words">
          {{ t('portfolio.greeting') }} <span class="text-accent">{{ name }}</span>
        </p>
        <p v-if="portfolio.quote" class="mt-2 border-l-2 border-accent/60 pl-2 text-[11px] leading-relaxed italic break-words">
          “{{ portfolio.quote }}”
        </p>
        <p
          v-for="(paragraph, index) in paragraphs"
          :key="index"
          class="mt-2 text-[11px] leading-relaxed whitespace-pre-line text-muted break-words"
        >
          {{ paragraph }}
        </p>
        <ul v-if="highlights.length" class="mt-3 grid grid-cols-3 gap-1.5">
          <li v-for="highlight in highlights" :key="highlight.label" class="min-w-0 rounded-lg bg-raised p-2 ring-1 ring-line">
            <p class="truncate text-[8px] font-semibold tracking-wider text-muted uppercase">{{ highlight.label }}</p>
            <p class="mt-0.5 text-[10px] leading-tight font-extrabold break-words">{{ highlight.value }}</p>
          </li>
        </ul>
      </div>
      <ul v-if="photoUrls.length" class="mt-4 grid grid-cols-2 gap-2">
        <li v-for="(url, index) in photoUrls" :key="index" class="overflow-hidden rounded-xl bg-surface ring-1 ring-line shadow-soft">
          <SmartImage :src="url" alt="" class="aspect-[3/4] w-full object-cover" />
        </li>
      </ul>
    </div>
  </div>
</template>
