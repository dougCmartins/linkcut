<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = withDefaults(defineProps<{ value?: number; label?: string; size?: 'xs' | 'sm' | 'md' }>(), {
  value: 0,
  label: undefined,
  size: 'md',
})
const model = defineModel<number | null>({ default: null })
const { t } = useI18n()

const filled = computed(() => Math.round(props.value))
const iconClass = computed(() => (props.size === 'xs' ? 'size-3' : props.size === 'sm' ? 'size-3.5' : 'size-4'))
</script>

<template>
  <div v-if="label" role="group" :aria-label="label" class="flex gap-1">
    <button
      v-for="star in 5"
      :key="star"
      type="button"
      class="flex size-11 items-center justify-center rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :aria-pressed="model === star"
      :aria-label="t('reviews.star', { count: star })"
      @click="model = star"
    >
      <svg class="size-7" :class="model !== null && star <= model ? 'text-accent' : 'text-line'" viewBox="0 0 24 24" aria-hidden="true">
        <path
          fill="currentColor"
          d="M12 2.6 14.7 8.8 21.4 9.6 16.5 14.2 17.8 20.8 12 17.5 6.2 20.8 7.5 14.2 2.6 9.6 9.3 8.8 12 2.6Z"
        />
      </svg>
    </button>
  </div>
  <div v-else class="flex gap-0.5" aria-hidden="true">
    <svg
      v-for="star in 5"
      :key="star"
      :class="[iconClass, star <= filled ? 'text-accent' : 'text-line']"
      viewBox="0 0 24 24"
    >
      <path
        fill="currentColor"
        d="M12 2.6 14.7 8.8 21.4 9.6 16.5 14.2 17.8 20.8 12 17.5 6.2 20.8 7.5 14.2 2.6 9.6 9.3 8.8 12 2.6Z"
      />
    </svg>
  </div>
</template>
