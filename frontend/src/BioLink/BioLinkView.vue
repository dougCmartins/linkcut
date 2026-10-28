<script setup lang="ts">
import { usePageMeta } from '@/composables/usePageMeta'
import { computed, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import AvatarImage from '@/components/ui/AvatarImage.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ProfileLinkIcon from '@/components/ui/ProfileLinkIcon.vue'
import ShareButton from '@/components/ui/ShareButton.vue'
import PrivacyLinks from '@/Legal/PrivacyLinks.vue'
import SmartImage from '@/components/ui/SmartImage.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'
import { useQueryOverlay } from '@/composables/useQueryOverlay'
import PublicPageStatus from '@/Domain/Settings/PublicPageStatus.vue'
import { usePublicPage } from '@/Domain/Settings/usePublicPage'
import { formatAverage } from '@/Domain/Review/review.api'
import StarRating from '@/Domain/Review/StarRating.vue'
import { buildWhatsappUrl } from '@/utils/phone'

const { t } = useI18n()
const publicPage = usePublicPage()

const profile = computed(() => publicPage.page?.profile)
// The barbershop's name and bio in search results and in the preview when the link is shared.
usePageMeta(
  () => profile.value?.name ?? '',
  () => profile.value?.bio || (profile.value ? t('bioLink.metaDescription', { name: profile.value.name }) : ''),
)
// The share sheet loads only when opened (keeps the public page light).
const ShareSheet = defineAsyncComponent(() => import('@/components/ui/ShareSheet.vue'))
// "?compartilhar=1": its own history entry, so the phone's back button closes the sheet, not the page.
const share = useQueryOverlay('compartilhar')
const pageUrl = computed(() => `${window.location.origin}/${publicPage.slug}`)
const whatsappUrl = computed(() => (profile.value ? buildWhatsappUrl(profile.value.whatsappNumber, profile.value.whatsappMessage) : ''))
const ratingAverage = computed(() => {
  const average = publicPage.page?.rating.average
  return average == null ? '' : formatAverage(average)
})
const ratingCount = computed(() => publicPage.page?.rating.count ?? 0)
</script>

<template>
  <PublicPageStatus v-if="!profile" :status="publicPage.status" @retry="publicPage.reload()" />
  <main v-else class="relative min-h-dvh overflow-hidden">
    <!-- Background photo with an overlay so text stays readable -->
    <div class="absolute inset-0 -z-10" aria-hidden="true">
      <SmartImage
        v-if="profile.backgroundUrl"
        :src="profile.backgroundUrl"
        alt=""
        class="size-full scale-105 object-cover opacity-60 blur-[2px]"
      />
      <div class="absolute inset-0 [background:var(--hero-overlay)]" />
    </div>

    <ShareButton class="absolute top-4 left-4 z-10" @click="share.open()" />
    <ShareSheet v-if="share.isOpen.value" :url="pageUrl" :name="profile.name" :image-url="profile.avatarUrl" @close="share.close()" />

    <div class="mx-auto flex min-h-dvh w-full max-w-md flex-col items-center px-5 pt-20 pb-10">
      <header class="flex flex-col items-center text-center">
        <AvatarImage :src="profile.avatarUrl" :alt="t('bioLink.avatarAlt', { name: profile.name })" />
        <div v-if="ratingAverage" class="mt-2.5 flex flex-col items-center gap-1">
          <p class="flex h-4 items-center gap-2 text-base font-bold tabular-nums">
            <StarRating :value="publicPage.page?.rating.average ?? 0" />
            <span>{{ ratingAverage }}</span>
          </p>
          <p class="text-xs font-semibold text-muted">{{ t('reviews.reviewCount', { count: ratingCount }, ratingCount) }}</p>
        </div>
        <h1 class="text-3xl leading-none break-words" :class="ratingAverage ? 'mt-2.5' : 'mt-5'">{{ profile.name }}</h1>
        <p v-if="profile.handle" class="mt-1.5 text-sm font-semibold text-accent">{{ profile.handle }}</p>
        <p v-if="profile.bio" class="mt-3 max-w-xs text-[15px] leading-relaxed text-muted break-words">{{ profile.bio }}</p>
      </header>

      <nav class="mt-10 flex w-full flex-col gap-5">
        <BaseButton variant="primary" size="lg" elevated block :to="`/${publicPage.slug}/agendar`">
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M16 3v4M8 3v4M3 10h18" />
          </svg>
          {{ t('bioLink.book') }}
        </BaseButton>
        <BaseButton variant="secondary" size="lg" elevated block :to="`/${publicPage.slug}/portfolio`">
          {{ t('bioLink.portfolio') }}
        </BaseButton>
        <BaseButton variant="secondary" size="lg" elevated block :href="whatsappUrl">
          <svg class="size-5 text-success" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path
              d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.3.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 2 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.2Z"
            />
          </svg>
          {{ t('bioLink.whatsapp') }}
        </BaseButton>
        <BaseButton
          v-for="(link, index) in profile.links"
          :key="index"
          variant="secondary"
          size="lg"
          elevated
          block
          :href="link.url"
        >
          <ProfileLinkIcon v-if="link.icon" :icon="link.icon" class="size-5" />
          {{ link.title }}
        </BaseButton>
      </nav>

      <footer class="mt-auto flex flex-col items-center gap-6 pt-12">
        <SocialLinks :links="profile.socials" :label="t('bioLink.socials')" />
        <RouterLink :to="{ name: 'barber-login', params: { slug: publicPage.slug } }" class="text-xs font-semibold tracking-wide text-muted transition hover:text-ink">
          {{ t('bioLink.poweredBy') }}
        </RouterLink>
        <PrivacyLinks />
      </footer>
    </div>
  </main>
</template>
