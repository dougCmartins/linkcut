<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import SmartImage from '@/components/ui/SmartImage.vue'
import SocialLinks from '@/components/ui/SocialLinks.vue'
import type { ProfileSettings } from './settings.model'

defineProps<{ profile: ProfileSettings; avatarUrl: string; backgroundUrl: string }>()
const { t } = useI18n()

const buttonClass =
  'flex h-11 w-full items-center justify-center rounded-button px-4 font-display text-[13px] font-extrabold tracking-tight'
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
      <p class="mt-3 font-display text-xl leading-tight font-extrabold tracking-tight break-words">{{ profile.name }}</p>
      <p class="mt-1 text-xs font-semibold text-accent">{{ profile.handle }}</p>
      <p class="mt-2 text-xs leading-relaxed text-muted break-words">{{ profile.bio }}</p>

      <div class="mt-6 grid w-full gap-3">
        <span :class="buttonClass" class="bg-accent text-on-accent shadow-[0_4px_0_0_var(--accent-edge)]">
          {{ t('bioLink.book') }}
        </span>
        <span :class="buttonClass" class="bg-surface ring-1 ring-line shadow-[0_4px_0_0_var(--neutral-edge)]">
          {{ t('bioLink.portfolio') }}
        </span>
        <span :class="buttonClass" class="bg-surface ring-1 ring-line shadow-[0_4px_0_0_var(--neutral-edge)]">
          {{ t('bioLink.whatsapp') }}
        </span>
      </div>
      <SocialLinks class="mt-6 scale-90" :links="profile.socials" :label="t('bioLink.socials')" />
    </div>
  </div>
</template>
