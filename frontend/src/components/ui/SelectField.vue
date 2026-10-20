<script setup lang="ts">
import { computed, useId } from 'vue'

/** A labelled <select>, styled like TextField. */
const props = withDefaults(
  defineProps<{ label: string; options: { value: string; label: string }[]; placeholder?: string; error?: string }>(),
  { placeholder: undefined, error: undefined },
)

const model = defineModel<string>({ required: true })
const id = useId()
const messageId = computed(() => (props.error ? `${id}-message` : undefined))
</script>

<template>
  <div>
    <label :for="id" class="block pl-2 text-xs font-semibold text-muted">{{ label }}</label>
    <select
      :id="id"
      v-model="model"
      :aria-invalid="Boolean(error)"
      :aria-describedby="messageId"
      class="mt-1.5 h-12 w-full min-w-0 rounded-full bg-surface px-5 text-[15px] text-ink ring-1 shadow-soft focus:ring-2 focus:ring-accent focus:outline-none"
      :class="error ? 'ring-danger' : 'ring-line'"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
    <p v-if="messageId" :id="messageId" class="mt-1.5 pl-2 text-xs text-danger">{{ error }}</p>
  </div>
</template>
