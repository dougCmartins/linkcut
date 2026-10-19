<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'

/**
 * "i" button that explains something. Opens on mouse hover and keyboard focus,
 * and on tap/click (touch screens have no hover). Esc or a tap outside closes it.
 */
const props = withDefaults(defineProps<{ label: string; align?: 'start' | 'end' }>(), { align: 'start' })

const id = useId()
const root = ref<HTMLElement | null>(null)
const isOpen = ref(false)
/** Opened by a click/tap: stays open when the mouse leaves. */
const isPinned = ref(false)
const panel = ref<HTMLElement | null>(null)
/** Horizontal shift (px) that keeps the panel inside the screen. */
const shift = ref(0)
const SCREEN_GUTTER_PX = 16

async function keepOnScreen(): Promise<void> {
  shift.value = 0
  await nextTick()
  const rect = panel.value?.getBoundingClientRect()
  if (!rect) return
  const maxRight = document.documentElement.clientWidth - SCREEN_GUTTER_PX
  if (rect.right > maxRight) shift.value = maxRight - rect.right
  else if (rect.left < SCREEN_GUTTER_PX) shift.value = SCREEN_GUTTER_PX - rect.left
}

function show(): void {
  isOpen.value = true
}

function hide(): void {
  isOpen.value = false
  isPinned.value = false
}

function onMouseLeave(): void {
  if (!isPinned.value) isOpen.value = false
}

function toggle(): void {
  if (isPinned.value) {
    hide()
    return
  }
  isPinned.value = true
  isOpen.value = true
}

function onFocusOut(event: FocusEvent): void {
  if (!root.value?.contains(event.relatedTarget as Node | null)) hide()
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape' && isOpen.value) {
    event.stopPropagation()
    hide()
  }
}

function onOutsidePointer(event: PointerEvent): void {
  if (!root.value?.contains(event.target as Node)) hide()
}

watch(isOpen, (open) => {
  if (open) {
    document.addEventListener('pointerdown', onOutsidePointer)
    void keepOnScreen()
  } else {
    document.removeEventListener('pointerdown', onOutsidePointer)
  }
})
onBeforeUnmount(() => document.removeEventListener('pointerdown', onOutsidePointer))
</script>

<template>
  <span ref="root" class="relative inline-flex" @mouseenter="show" @mouseleave="onMouseLeave" @keydown="onKeydown">
    <button
      type="button"
      :aria-label="props.label"
      :aria-describedby="id"
      :aria-expanded="isOpen"
      class="flex size-7 items-center justify-center rounded-full text-muted transition hover:bg-raised hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent"
      @focus="show"
      @focusout="onFocusOut"
      @click="toggle"
    >
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
        <circle cx="12" cy="12" r="9" />
        <path d="M12 11v5M12 8h.01" />
      </svg>
    </button>
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-show="isOpen"
        :id="id"
        ref="panel"
        role="tooltip"
        class="absolute top-full z-30 mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-card bg-ink p-4 text-left text-sm leading-relaxed font-normal tracking-normal text-page normal-case shadow-float"
        :class="align === 'end' ? 'right-0' : 'left-0'"
        :style="{ marginLeft: `${shift}px`, marginRight: `${-shift}px` }"
      >
        <slot />
      </div>
    </Transition>
  </span>
</template>
