<script setup lang="ts">
import { SocialNetwork } from '@/types/socialNetwork'

defineProps<{ network: SocialNetwork }>()

const ICON_PATHS: Record<SocialNetwork, string> = {
  [SocialNetwork.Instagram]:
    'M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm4.5 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm5.5-1.25a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z',
  [SocialNetwork.TikTok]:
    'M16.6 2c.3 2.3 1.7 3.9 4.4 4.1v3.3c-1.6.1-3-.4-4.4-1.3v6.4c0 8-8.7 10.5-12.2 4.8-2.3-3.7-.9-10.2 6.4-10.4v3.5c-.6.1-1.2.2-1.7.4-1.6.5-2.5 1.6-2.2 3.4.5 3.6 7.1 4.6 6.6-2.4V2h3.1Z',
  [SocialNetwork.YouTube]:
    'M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4a2.5 2.5 0 0 0-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8ZM10 15V9l5.2 3L10 15Z',
}
</script>

<template>
  <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path :d="ICON_PATHS[network]" fill-rule="evenodd" />
  </svg>
</template>
