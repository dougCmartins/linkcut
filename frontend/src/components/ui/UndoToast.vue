<script setup lang="ts">
/** Bottom notice with an undo action. The parent decides when it shows (v-if). */
defineProps<{ message: string; actionLabel: string }>()
const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div class="fixed inset-x-0 bottom-0 z-40 px-4 pb-[max(1rem,env(safe-area-inset-bottom))]" role="status" aria-live="polite">
    <div class="mx-auto flex max-w-md items-center gap-3 rounded-card bg-ink py-3 pr-3 pl-4 text-page shadow-float">
      <p class="min-w-0 flex-1 text-sm font-semibold">{{ message }}</p>
      <button
        type="button"
        class="shrink-0 rounded-full px-3 py-2 text-sm font-extrabold text-accent hover:bg-page/10"
        @click="emit('action')"
      >
        {{ actionLabel }}
      </button>
    </div>
  </div>
</template>
