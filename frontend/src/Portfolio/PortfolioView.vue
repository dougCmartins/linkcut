<script setup lang="ts">
import { usePageMeta } from '@/composables/usePageMeta'
import { computed, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import ShareButton from '@/components/ui/ShareButton.vue'
import PrivacyLinks from '@/Legal/PrivacyLinks.vue'
import SmartImage from '@/components/ui/SmartImage.vue'
import AboutCard from './AboutCard.vue'
import WorkGallery from './WorkGallery.vue'
import StickyBookingBar from './StickyBookingBar.vue'
import { useQueryOverlay } from '@/composables/useQueryOverlay'
import PublicPageStatus from '@/Domain/Settings/PublicPageStatus.vue'
import { usePublicPage } from '@/Domain/Settings/usePublicPage'
import ReviewCarousel from '@/Domain/Review/ReviewCarousel.vue'

const { t } = useI18n()
const router = useRouter()
const publicPage = usePublicPage()

const name = computed(() => publicPage.page?.profile.name ?? '')
const portfolio = computed(() => publicPage.page?.portfolio)
usePageMeta(
  () => (name.value ? t('portfolio.metaTitle', { name: name.value }) : ''),
  () => portfolio.value?.quote || portfolio.value?.story || '',
)
const ShareSheet = defineAsyncComponent(() => import('@/components/ui/ShareSheet.vue'))
const share = useQueryOverlay('compartilhar')
const pageUrl = computed(() => `${window.location.origin}/${publicPage.slug}/portfolio`)
const photos = computed(() => (portfolio.value?.photos ?? []).map((photo, index) => ({ id: String(index), ...photo })))
const featuredReviews = computed(() => publicPage.page?.featuredReviews ?? [])
</script>

<template>
  <PublicPageStatus v-if="!portfolio" :status="publicPage.status" @retry="publicPage.reload()" />
  <main v-else class="pb-32">
    <div class="relative h-80 overflow-hidden">
      <SmartImage :src="portfolio.coverUrl" :alt="t('portfolio.coverAlt', { name })" class="size-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-page" aria-hidden="true" />
      <RouterLink
        :to="`/${publicPage.slug}`"
        :aria-label="t('portfolio.back')"
        class="absolute top-4 left-4 flex size-10 items-center justify-center rounded-full bg-surface/80 text-ink ring-1 ring-line shadow-soft backdrop-blur"
      >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </RouterLink>
      <ShareButton class="absolute top-4 left-16" @click="share.open()" />
    </div>
    <ShareSheet
      v-if="share.isOpen.value"
      :url="pageUrl"
      :name="name"
      :image-url="portfolio.coverUrl || publicPage.page?.profile.avatarUrl"
      :title="t('share.titlePortfolio')"
      @close="share.close()"
    />

    <div class="mx-auto -mt-24 flex w-full max-w-md flex-col gap-10 px-4">
      <AboutCard :name="name" :portfolio="portfolio" />
      <ReviewCarousel v-if="featuredReviews.length" :reviews="featuredReviews" />
      <WorkGallery v-if="photos.length" :photos="photos" :title="t('portfolio.galleryTitle')" :subtitle="t('portfolio.gallerySubtitle')" />
      <PrivacyLinks />
    </div>

    <StickyBookingBar @book="router.push(`/${publicPage.slug}/portfolio/agendar`)" />
  </main>
</template>
