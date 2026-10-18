<script setup lang="ts">
withDefaults(defineProps<{ as?: string; floating?: boolean }>(), { as: 'section', floating: false })
</script>

<template>
  <component
    :is="as"
    class="rounded-card bg-surface ring-1 ring-line"
    :class="floating ? 'shadow-float' : 'shadow-soft'"
  >
    <slot />
  </component>
</template>
