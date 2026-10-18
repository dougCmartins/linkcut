<script setup lang="ts">
import BaseCard from '@/components/ui/BaseCard.vue'

withDefaults(defineProps<{ title: string; description?: string }>(), { description: undefined })
</script>

<template>
  <BaseCard class="p-5">
    <h3 class="text-lg">{{ title }}</h3>
    <p v-if="description" class="mt-1 text-sm text-muted">{{ description }}</p>
    <div class="mt-5 grid gap-4">
      <slot />
    </div>
  </BaseCard>
</template>
