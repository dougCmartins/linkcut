<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

/** "Já tem conta? Entrar": switches between sign-up and sign-in on the same page. */
defineProps<{ question: string; action: string; to: RouteLocationRaw }>()
</script>

<template>
  <p class="mt-8 text-center text-sm">
    {{ question }}
    <RouterLink :to="to" class="font-semibold text-accent underline underline-offset-4">{{ action }}</RouterLink>
  </p>
</template>
