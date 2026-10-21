<script setup lang="ts">
import { nextTick, ref, useTemplateRef, watch } from 'vue'

/** One box per digit. Supports typing, backspace, arrows, paste and one-time-code autofill. */
const props = withDefaults(defineProps<{ length?: number; label: string; disabled?: boolean; invalid?: boolean }>(), {
  length: 6,
  disabled: false,
  invalid: false,
})
const code = defineModel<string>({ required: true })
const emit = defineEmits<{ complete: [code: string] }>()
const boxes = useTemplateRef<HTMLInputElement[]>('boxes')

const slots = ref<string[]>(Array.from({ length: props.length }, () => ''))

// The parent can clear the code (e.g. after a wrong attempt).
watch(code, (value) => {
  if (value === '') slots.value = slots.value.map(() => '')
})

function focusBox(index: number): void {
  const target = boxes.value?.[Math.max(0, Math.min(index, props.length - 1))]
  target?.focus()
  target?.select()
}

function commit(next: string[]): void {
  const value = next.join('')
  slots.value = next
  code.value = value
  // Use the new value: the model only updates after the parent re-renders.
  if (next.every(Boolean)) emit('complete', value)
}

function fillFrom(start: number, typed: string): void {
  const clean = typed.replace(/\D/g, '').slice(0, props.length - start)
  if (!clean) return
  const next = slots.value.slice()
  clean.split('').forEach((digit, offset) => (next[start + offset] = digit))
  commit(next)
  void nextTick(() => focusBox(start + clean.length))
}

function onInput(index: number, event: Event): void {
  const input = event.target as HTMLInputElement
  const typed = input.value
  input.value = slots.value[index] ?? ''
  fillFrom(index, typed)
}

function onKeydown(index: number, event: KeyboardEvent): void {
  if (event.key === 'Backspace') {
    event.preventDefault()
    const target = slots.value[index] ? index : index - 1
    if (target < 0) return
    const next = slots.value.slice()
    next[target] = ''
    commit(next)
    focusBox(target)
  } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
    event.preventDefault()
    focusBox(index + (event.key === 'ArrowLeft' ? -1 : 1))
  }
}

function onPaste(index: number, event: ClipboardEvent): void {
  event.preventDefault()
  fillFrom(index, event.clipboardData?.getData('text') ?? '')
}

defineExpose({
  focus: () => {
    const firstEmpty = slots.value.findIndex((digit) => !digit)
    focusBox(firstEmpty === -1 ? props.length - 1 : firstEmpty)
  },
})
</script>

<template>
  <div role="group" :aria-label="label" class="flex justify-between gap-2">
    <input
      v-for="(digit, index) in slots"
      :key="index"
      ref="boxes"
      :value="digit"
      type="text"
      inputmode="numeric"
      pattern="[0-9]*"
      :autocomplete="index === 0 ? 'one-time-code' : 'off'"
      :maxlength="index === 0 ? length : 1"
      :disabled="disabled"
      :aria-label="`${label}: ${index + 1}/${length}`"
      :aria-invalid="invalid"
      class="h-12 w-full min-w-0 rounded-xl bg-surface text-center font-display text-xl font-bold text-ink ring-1 shadow-soft transition focus:ring-2 focus:ring-accent focus:outline-none disabled:opacity-50"
      :class="invalid ? 'ring-danger' : digit ? 'ring-accent/60' : 'ring-line'"
      @input="onInput(index, $event)"
      @keydown="onKeydown(index, $event)"
      @paste="onPaste(index, $event)"
      @focus="($event.target as HTMLInputElement).select()"
    />
  </div>
</template>
