<script setup lang="ts">
/** The sections of a legal text (policy or terms), as lists. */
defineProps<{ sections: { key: string; title: string; items: string[] }[]; idPrefix: string }>()
</script>

<template>
  <section v-for="section in sections" :key="section.key" class="mt-8" :aria-labelledby="`${idPrefix}-${section.key}`">
    <h2 :id="`${idPrefix}-${section.key}`" class="text-lg">{{ section.title }}</h2>
    <ul class="mt-3 grid list-disc gap-2 pl-5 text-sm leading-relaxed">
      <li v-for="(item, index) in section.items" :key="index">{{ item }}</li>
    </ul>
  </section>
</template>
