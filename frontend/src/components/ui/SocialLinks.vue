<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SocialIcon from './SocialIcon.vue'
import type { SocialLink } from '@/Domain/Settings/settings.model'

const MAX_LINKS = 3

const props = defineProps<{ links: SocialLink[]; label: string }>()
const { t } = useI18n()

const visibleLinks = computed(() => props.links.filter((link) => link.url.trim()).slice(0, MAX_LINKS))
</script>

<template>
  <nav v-if="visibleLinks.length" :aria-label="label">
    <ul class="flex items-center justify-center gap-4">
      <li v-for="link in visibleLinks" :key="link.network">
        <a
          :href="link.url"
          target="_blank"
          rel="noopener noreferrer"
          :aria-label="t(`socials.${link.network}`)"
          class="flex size-11 items-center justify-center rounded-full bg-surface text-ink ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 hover:text-accent"
        >
          <SocialIcon :network="link.network" class="size-5" />
        </a>
      </li>
    </ul>
  </nav>
</template>
