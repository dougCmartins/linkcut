<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BaseCard from '@/components/ui/BaseCard.vue'
import HighlightChips from './HighlightChips.vue'
import type { BarberProfile } from '@/mocks/barber'

defineProps<{ profile: BarberProfile }>()
const { t } = useI18n()
</script>

<template>
  <BaseCard floating class="relative px-6 pt-7 pb-6">
    <span class="block h-0.5 w-10 rounded-full bg-accent" aria-hidden="true" />
    <p class="mt-4 text-[11px] font-bold tracking-[0.22em] text-accent uppercase">{{ t('portfolio.eyebrow') }}</p>
    <h1 class="mt-2 text-[28px] leading-[1.05]">
      {{ t('portfolio.greeting') }}
      <span class="text-accent">{{ profile.name }}</span>
    </h1>
    <blockquote class="mt-4 border-l-2 border-accent/60 pl-4 text-[15px] leading-relaxed text-ink/90 italic">
      “{{ profile.quote }}”
    </blockquote>
    <div class="mt-4 space-y-3 text-[15px] leading-relaxed text-muted">
      <p v-for="(paragraph, index) in profile.story" :key="index">{{ paragraph }}</p>
    </div>
    <HighlightChips :highlights="profile.highlights" class="mt-6" />
  </BaseCard>
</template>
