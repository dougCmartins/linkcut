<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import SmartImage from '@/components/ui/SmartImage.vue'
import StarRating from '@/Domain/Review/StarRating.vue'
import { fetchReviews } from '@/Domain/Review/review.api'
import { splitParagraphs, type FeaturedReview, type PortfolioSettings } from './settings.model'

const props = defineProps<{
  portfolio: PortfolioSettings
  name: string
  coverUrl: string
  photoUrls: string[]
}>()
const { t } = useI18n()

const paragraphs = computed(() => splitParagraphs(props.portfolio.story))
const highlights = computed(() => props.portfolio.highlights.filter((highlight) => highlight.value.trim()))
const featured = ref<FeaturedReview[]>([])

/** The phone shows the chosen comments only. A failed read leaves the preview without them. */
async function loadFeatured(): Promise<void> {
  if (!props.portfolio.showReviews) {
    featured.value = []
    return
  }
  try {
    const reviews = await fetchReviews()
    featured.value = reviews
      .filter((review) => review.comment && !review.hiddenAt && review.featuredPosition !== null)
      .sort((a, b) => (a.featuredPosition ?? 0) - (b.featuredPosition ?? 0))
      .map((review) => ({ firstName: review.firstName, rating: review.rating, comment: review.comment ?? '' }))
  } catch {
    featured.value = []
  }
}

onMounted(() => void loadFeatured())
watch(() => props.portfolio.showReviews, () => void loadFeatured())
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
      <div v-if="featured.length" class="mt-4">
        <p class="text-xs font-extrabold">{{ t('reviews.carouselTitle') }}</p>
        <ul class="scrollbar-none mt-2 flex gap-2 overflow-x-auto">
          <li
            v-for="(review, index) in featured"
            :key="index"
            class="w-40 shrink-0 rounded-xl bg-surface p-3 ring-1 ring-line"
          >
            <p class="text-xs font-bold break-words">{{ review.firstName }}</p>
            <div class="mt-1">
              <StarRating :value="review.rating" size="xs" />
            </div>
            <p class="mt-1 text-[10px] leading-snug break-words">{{ review.comment }}</p>
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
