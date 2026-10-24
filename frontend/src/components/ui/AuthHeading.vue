<script setup lang="ts">
/** The big title of the sign-up and sign-in pages. */
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <header class="mb-8">
    <h1 class="text-4xl leading-[1.1] font-extrabold tracking-tight lg:text-5xl">{{ title }}</h1>
    <p v-if="subtitle" class="mt-3 text-base text-muted">{{ subtitle }}</p>
  </header>
</template>
