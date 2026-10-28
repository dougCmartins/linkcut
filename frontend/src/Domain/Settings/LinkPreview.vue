<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SmartImage from '@/components/ui/SmartImage.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'
import ProfileLinkIcon from '@/components/ui/ProfileLinkIcon.vue'
import { buttonSkinClasses } from '@/components/ui/buttonSkin'
import { useButtonStyle } from '@/composables/useButtonStyle'
import { formatAverage } from '@/Domain/Review/review.api'
import StarRating from '@/Domain/Review/StarRating.vue'
import type { ProfileSettings } from './settings.model'

withDefaults(
  defineProps<{
    profile: ProfileSettings
    avatarUrl: string
    backgroundUrl: string
    /** Shown under the photo only when the barber leaves the average on. */
    rating?: { average: number; count: number } | null
  }>(),
  { rating: null },
)
const { t } = useI18n()
const buttonStyle = useButtonStyle()

const baseClass =
  'flex h-11 w-full items-center justify-center rounded-button px-4 font-display text-[13px] font-extrabold tracking-tight'
const primaryClass = computed(() => [baseClass, buttonSkinClasses(buttonStyle.value, 'primary')])
const secondaryClass = computed(() => [baseClass, buttonSkinClasses(buttonStyle.value, 'secondary')])
</script>

<template>
  <div class="relative min-h-full overflow-hidden px-4 pt-10 pb-6">
    <div class="absolute inset-0" aria-hidden="true">
      <SmartImage v-if="backgroundUrl" :src="backgroundUrl" alt="" class="size-full object-cover opacity-60" />
      <div class="absolute inset-0 [background:var(--hero-overlay)]" />
    </div>
    <div class="relative flex flex-col items-center text-center">
      <div class="size-20 overflow-hidden rounded-full ring-4 ring-surface shadow-float">
        <SmartImage :src="avatarUrl" alt="" class="size-full object-cover" />
      </div>
      <div v-if="rating" class="mt-2 flex flex-col items-center gap-0.5">
        <p class="flex items-center gap-1 text-xs font-bold tabular-nums">
          <StarRating :value="rating.average" size="xs" />
          <span>{{ formatAverage(rating.average) }}</span>
        </p>
        <p class="text-[10px] font-semibold text-muted">{{ t('reviews.reviewCount', { count: rating.count }, rating.count) }}</p>
      </div>
      <p class="font-display text-xl leading-tight font-extrabold tracking-tight break-words" :class="rating ? 'mt-2' : 'mt-3'">
        {{ profile.name }}
      </p>
      <p class="mt-1 text-xs font-semibold text-accent">{{ profile.handle }}</p>
      <p class="mt-2 text-xs leading-relaxed text-muted break-words">{{ profile.bio }}</p>

      <div class="mt-6 grid w-full gap-3.5" data-testid="preview-buttons">
        <span :class="primaryClass">{{ t('bioLink.book') }}</span>
        <span :class="secondaryClass">{{ t('bioLink.portfolio') }}</span>
        <span :class="secondaryClass">{{ t('bioLink.whatsapp') }}</span>
        <span v-for="(link, index) in profile.links" :key="index" :class="[secondaryClass, 'gap-2']">
          <ProfileLinkIcon v-if="link.icon" :icon="link.icon" class="size-4" />
          {{ link.title }}
        </span>
      </div>
      <SocialLinks class="mt-6 scale-90" :links="profile.socials" :label="t('bioLink.socials')" />
    </div>
  </div>
</template>
