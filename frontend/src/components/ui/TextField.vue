<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    label: string
    error?: string
    hint?: string
    type?: 'text' | 'email' | 'password' | 'tel' | 'url' | 'date' | 'time' | 'number'
    maxlength?: number
    placeholder?: string
    inputmode?: 'text' | 'email' | 'tel' | 'url' | 'numeric' | 'decimal'
    autocomplete?: string
    min?: string | number
    max?: string | number
    step?: string | number
  }>(),
  {
    error: undefined,
    hint: undefined,
    type: 'text',
    maxlength: undefined,
    placeholder: undefined,
    inputmode: undefined,
    autocomplete: 'off',
    min: undefined,
    max: undefined,
    step: undefined,
  },
)

const model = defineModel<string>({ required: true })
const id = useId()
const messageId = computed(() => (props.error || props.hint ? `${id}-message` : undefined))
// Date/time pickers carry their own icon, so they get less padding to fit narrow columns.
const paddingClass = computed(() => (props.type === 'date' || props.type === 'time' ? 'px-3.5 text-sm' : 'px-5 text-base'))
</script>

<template>
  <div>
    <label :for="id" class="block pl-2 text-xs font-semibold text-muted">{{ label }}</label>
    <input
      :id="id"
      v-model="model"
      :type="type"
      :maxlength="maxlength"
      :placeholder="placeholder"
      :inputmode="inputmode"
      :autocomplete="autocomplete"
      :min="min"
      :max="max"
      :step="step"
      :aria-invalid="Boolean(error)"
      :aria-describedby="messageId"
      class="mt-1.5 h-11 w-full min-w-0 rounded-full bg-surface text-ink ring-1 shadow-soft placeholder:text-muted/70 focus:ring-2 focus:ring-accent focus:outline-none"
      :class="[paddingClass, error ? 'ring-danger' : 'ring-line']"
    />
    <p v-if="messageId" :id="messageId" class="mt-1.5 pl-2 text-xs" :class="error ? 'text-danger' : 'text-muted'">
      {{ error ?? hint }}
    </p>
  </div>
</template>
