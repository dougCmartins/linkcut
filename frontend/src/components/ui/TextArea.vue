<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(
  defineProps<{ label: string; maxlength: number; error?: string; hint?: string; rows?: number; placeholder?: string }>(),
  { error: undefined, hint: undefined, rows: 4, placeholder: undefined },
)

const model = defineModel<string>({ required: true })
const id = useId()
const messageId = `${id}-message`
const counter = computed(() => `${model.value.length}/${props.maxlength}`)
</script>

<template>
  <div>
    <label :for="id" class="block pl-2 text-xs font-semibold text-muted">{{ label }}</label>
    <textarea
      :id="id"
      v-model="model"
      :rows="rows"
      :maxlength="maxlength"
      :placeholder="placeholder"
      :aria-invalid="Boolean(error)"
      :aria-describedby="messageId"
      class="mt-1.5 block w-full resize-y rounded-[1.25rem] bg-surface px-5 py-3.5 text-[15px] leading-relaxed text-ink ring-1 shadow-soft placeholder:text-muted/70 focus:ring-2 focus:ring-accent focus:outline-none"
      :class="error ? 'ring-danger' : 'ring-line'"
    />
    <div :id="messageId" class="mt-1.5 flex justify-between gap-3 px-2 text-xs">
      <span :class="error ? 'text-danger' : 'text-muted'">{{ error ?? hint }}</span>
      <span class="shrink-0 text-muted tabular-nums">{{ counter }}</span>
    </div>
  </div>
</template>
