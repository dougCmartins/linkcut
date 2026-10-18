<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'

/**
 * Horizontal row that slides with touch, trackpad or mouse drag, without a visible scrollbar.
 * Stays inside its parent's width, fades the edge that has more content
 * and keeps the selected item (aria-checked / aria-selected) in view.
 */
const props = withDefaults(
  defineProps<{
    label: string
    /** Changing it scrolls the selected item into view. */
    selectedKey?: string | number | null
    role?: 'radiogroup' | 'tablist'
  }>(),
  { selectedKey: null, role: 'radiogroup' },
)

const DRAG_THRESHOLD_PX = 6
const EDGE_TOLERANCE_PX = 2

const scroller = useTemplateRef<HTMLElement>('scroller')
const canScrollStart = ref(false)
const canScrollEnd = ref(false)

function updateEdges(): void {
  const element = scroller.value
  if (!element) return
  canScrollStart.value = element.scrollLeft > EDGE_TOLERANCE_PX
  canScrollEnd.value = element.scrollLeft + element.clientWidth < element.scrollWidth - EDGE_TOLERANCE_PX
}

const FADE = '24px'
const maskStyle = computed(() => {
  if (!canScrollStart.value && !canScrollEnd.value) return {}
  const start = canScrollStart.value ? `transparent 0, #000 ${FADE}` : '#000 0'
  const end = canScrollEnd.value ? `#000 calc(100% - ${FADE}), transparent 100%` : '#000 100%'
  const mask = `linear-gradient(to right, ${start}, ${end})`
  return { maskImage: mask, WebkitMaskImage: mask }
})

function scrollSelectedIntoView(behavior: ScrollBehavior = 'smooth'): void {
  const element = scroller.value
  const selected = element?.querySelector<HTMLElement>('[aria-checked="true"], [aria-selected="true"]')
  if (!element || !selected) return
  const left = Math.max(0, selected.offsetLeft - (element.clientWidth - selected.offsetWidth) / 2)
  if (typeof element.scrollTo === 'function') element.scrollTo({ left, behavior })
  else element.scrollLeft = left
}

watch(
  () => props.selectedKey,
  () => nextTick(() => scrollSelectedIntoView()),
)

// Mouse drag (touch and trackpads already scroll natively).
let dragStartX = 0
let dragStartScroll = 0
let isPointerDown = false
const isDragging = ref(false)

function onPointerDown(event: PointerEvent): void {
  if (event.pointerType !== 'mouse' || event.button !== 0 || !scroller.value) return
  isPointerDown = true
  dragStartX = event.clientX
  dragStartScroll = scroller.value.scrollLeft
}

function onPointerMove(event: PointerEvent): void {
  if (!isPointerDown || !scroller.value) return
  const distance = event.clientX - dragStartX
  if (!isDragging.value && Math.abs(distance) < DRAG_THRESHOLD_PX) return
  if (!isDragging.value) {
    isDragging.value = true
    scroller.value.setPointerCapture?.(event.pointerId)
  }
  scroller.value.scrollLeft = dragStartScroll - distance
}

function onPointerUp(): void {
  isPointerDown = false
  // Let the click that ends a drag be swallowed before resetting.
  requestAnimationFrame(() => (isDragging.value = false))
}

/** A drag should not also select the item under the mouse. */
function onClickCapture(event: MouseEvent): void {
  if (!isDragging.value) return
  event.preventDefault()
  event.stopPropagation()
}

let resizeObserver: ResizeObserver | undefined

onMounted(() => {
  updateEdges()
  scrollSelectedIntoView('auto')
  if (typeof ResizeObserver !== 'undefined' && scroller.value) {
    resizeObserver = new ResizeObserver(updateEdges)
    resizeObserver.observe(scroller.value)
  }
})

onBeforeUnmount(() => resizeObserver?.disconnect())
</script>

<template>
  <div
    ref="scroller"
    :role="role"
    :aria-label="label"
    class="scrollbar-none relative flex w-full min-w-0 snap-x snap-proximity overflow-x-auto overscroll-x-contain scroll-smooth"
    :class="isDragging ? 'cursor-grabbing snap-none select-none [scroll-behavior:auto]' : 'cursor-grab'"
    :style="maskStyle"
    @scroll.passive="updateEdges"
    @pointerdown="onPointerDown"
    @pointermove="onPointerMove"
    @pointerup="onPointerUp"
    @pointercancel="onPointerUp"
    @click.capture="onClickCapture"
  >
    <slot />
  </div>
</template>
