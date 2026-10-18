<script setup lang="ts">
import { useId } from 'vue'

withDefaults(defineProps<{ label: string; description?: string }>(), { description: undefined })
const isOn = defineModel<boolean>({ required: true })
const id = useId()
</script>

<template>
  <div class="flex items-center justify-between gap-4">
    <div class="min-w-0">
      <span :id="`${id}-label`" class="block text-sm font-semibold">{{ label }}</span>
      <span v-if="description" :id="`${id}-description`" class="mt-0.5 block text-xs text-muted">{{ description }}</span>
    </div>
    <button
      type="button"
      role="switch"
      :aria-checked="isOn"
      :aria-labelledby="`${id}-label`"
      :aria-describedby="description ? `${id}-description` : undefined"
      class="relative h-7 w-12 shrink-0 rounded-full ring-1 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :class="isOn ? 'bg-accent ring-accent' : 'bg-raised ring-line'"
      @click="isOn = !isOn"
    >
      <span
        class="absolute top-1 left-1 size-5 rounded-full shadow-soft transition-transform"
        :class="isOn ? 'translate-x-5 bg-on-accent' : 'bg-muted'"
        aria-hidden="true"
      />
    </button>
  </div>
</template>
