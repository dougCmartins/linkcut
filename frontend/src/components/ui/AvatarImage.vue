<script setup lang="ts">
import SmartImage from './SmartImage.vue'

withDefaults(defineProps<{ src: string; alt: string; size?: 'md' | 'xl' }>(), { size: 'xl' })
</script>

<template>
  <div
    class="overflow-hidden rounded-full ring-4 ring-surface shadow-float"
    :class="size === 'xl' ? 'size-28' : 'size-12 ring-2'"
  >
    <SmartImage :src="src" :alt="alt" class="size-full object-cover" />
  </div>
</template>
