<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { FeaturedReview } from '@/Domain/Settings/settings.model'
import StarRating from './StarRating.vue'

defineProps<{ reviews: FeaturedReview[] }>()
const { t } = useI18n()
</script>

<template>
  <section aria-labelledby="reviews-carousel-title">
    <h2 id="reviews-carousel-title" class="text-lg font-extrabold">{{ t('reviews.carouselTitle') }}</h2>
    <ul class="scrollbar-none mt-4 flex snap-x snap-mandatory gap-3 overflow-x-auto">
      <li
        v-for="(review, index) in reviews"
        :key="index"
        class="flex w-[250px] shrink-0 snap-start flex-col gap-2 rounded-card bg-surface p-4 ring-1 ring-line shadow-soft"
      >
        <p class="text-sm font-bold break-words">{{ review.firstName }}</p>
        <StarRating :value="review.rating" size="sm" />
        <p class="text-[13px] leading-snug break-words">{{ review.comment }}</p>
      </li>
    </ul>
  </section>
</template>
