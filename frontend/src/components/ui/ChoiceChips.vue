<script setup lang="ts" generic="T extends string | number">
export interface ChoiceOption<V> {
  value: V
  label: string
}

defineProps<{ options: ChoiceOption<T>[]; label: string }>()
const selected = defineModel<T>({ required: true })
</script>

<template>
  <div>
    <p class="pl-2 text-xs font-semibold text-muted">{{ label }}</p>
    <div role="radiogroup" :aria-label="label" class="mt-1.5 flex flex-wrap gap-2">
      <button
        v-for="option in options"
        :key="option.value"
        type="button"
        role="radio"
        :aria-checked="option.value === selected"
        class="h-10 rounded-full px-4 font-display text-sm font-extrabold tracking-tight ring-1 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :class="
          option.value === selected
            ? 'bg-accent text-on-accent ring-accent shadow-soft'
            : 'bg-surface text-ink ring-line hover:-translate-y-0.5'
        "
        @click="selected = option.value"
      >
        {{ option.label }}
      </button>
    </div>
  </div>
</template>
