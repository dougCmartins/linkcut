<script setup lang="ts">
import SmartImage from '@/components/ui/SmartImage.vue'
import type { PortfolioPhoto } from '@/mocks/barber'

defineProps<{ photos: PortfolioPhoto[]; title: string; subtitle: string }>()
</script>

<template>
  <section>
    <h2 class="text-xl">{{ title }}</h2>
    <p class="mt-1 text-sm text-muted">{{ subtitle }}</p>
    <ul class="mt-4 grid grid-cols-2 gap-3 pb-5">
      <li
        v-for="(photo, index) in photos.slice(0, 4)"
        :key="photo.id"
        class="group overflow-hidden rounded-card bg-surface shadow-soft ring-1 ring-line"
        :class="index % 2 === 1 ? 'translate-y-5' : ''"
      >
        <SmartImage
          :src="photo.url"
          :alt="photo.alt"
          lazy
          class="aspect-[3/4] w-full object-cover transition duration-500 group-hover:scale-105"
        />
      </li>
    </ul>
  </section>
</template>
