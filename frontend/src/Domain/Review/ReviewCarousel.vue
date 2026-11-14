<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FeaturedReview } from '@/Domain/Settings/settings.model'
import StarRating from './StarRating.vue'

defineProps<{ reviews: FeaturedReview[] }>()
const { t } = useI18n()

const DRAG_THRESHOLD_PX = 6
const EDGE_TOLERANCE_PX = 2

const list = useTemplateRef<HTMLElement>('list')
const isDragging = ref(false)
const canGoBack = ref(false)
const canGoForward = ref(false)

/** The arrows switch off at the first and the last card. */
function updateEdges(): void {
  const element = list.value
  if (!element) return
  canGoBack.value = element.scrollLeft > EDGE_TOLERANCE_PX
  canGoForward.value = element.scrollLeft + element.clientWidth < element.scrollWidth - EDGE_TOLERANCE_PX
}

// The side with more cards fades out (like HorizontalScroller), instead of cutting the card and its shadow straight.
const FADE = '32px'
const fadeStyle = computed(() => {
  if (!canGoBack.value && !canGoForward.value) return {}
  const start = canGoBack.value ? `transparent 0, #000 ${FADE}` : '#000 0'
  const end = canGoForward.value ? `#000 calc(100% - ${FADE}), transparent 100%` : '#000 100%'
  const mask = `linear-gradient(to right, ${start}, ${end})`
  return { maskImage: mask, WebkitMaskImage: mask }
})

/** One card (plus the gap) at a time, sliding instead of jumping. */
function go(direction: 1 | -1): void {
  const element = list.value
  const card = element?.querySelector('li')
  if (!element || !card) return
  const gap = parseFloat(getComputedStyle(element).columnGap) || 0
  element.scrollBy({ left: direction * (card.offsetWidth + gap), behavior: 'smooth' })
}

let dragStartX = 0
let dragStartScroll = 0
let isPointerDown = false

function onPointerDown(event: PointerEvent): void {
  if (event.pointerType !== 'mouse' || event.button !== 0 || !list.value) return
  isPointerDown = true
  dragStartX = event.clientX
  dragStartScroll = list.value.scrollLeft
}

function onPointerMove(event: PointerEvent): void {
  if (!isPointerDown || !list.value) return
  const distance = event.clientX - dragStartX
  if (!isDragging.value && Math.abs(distance) < DRAG_THRESHOLD_PX) return
  if (!isDragging.value) {
    isDragging.value = true
    list.value.setPointerCapture?.(event.pointerId)
  }
  list.value.scrollLeft = dragStartScroll - distance
}

function onPointerUp(): void {
  isPointerDown = false
  requestAnimationFrame(() => {
    isDragging.value = false
  })
}

/** The mouse wheel moves the cards. At either end the page keeps scrolling. */
function onWheel(event: WheelEvent): void {
  const element = list.value
  if (!element) return
  const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY
  if (delta === 0) return
  const max = element.scrollWidth - element.clientWidth
  const next = Math.min(max, Math.max(0, element.scrollLeft + delta))
  if (next === element.scrollLeft) return
  event.preventDefault()
  element.scrollLeft = next
}

onMounted(() => {
  list.value?.addEventListener('wheel', onWheel, { passive: false })
  window.addEventListener('resize', updateEdges)
  updateEdges()
})
onBeforeUnmount(() => {
  list.value?.removeEventListener('wheel', onWheel)
  window.removeEventListener('resize', updateEdges)
})

const arrowClass =
  'flex size-10 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft transition hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-default disabled:opacity-40 disabled:hover:text-inherit'
</script>

<template>
  <section aria-labelledby="reviews-carousel-title">
    <div class="flex items-center justify-between gap-3">
      <h2 id="reviews-carousel-title" class="text-lg font-extrabold">{{ t('reviews.carouselTitle') }}</h2>
      <div v-if="canGoBack || canGoForward" class="flex shrink-0 gap-2">
        <button type="button" :class="arrowClass" :disabled="!canGoBack" :aria-label="t('reviews.previous')" @click="go(-1)">
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" />
          </svg>
        </button>
        <button type="button" :class="arrowClass" :disabled="!canGoForward" :aria-label="t('reviews.next')" @click="go(1)">
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M9 18l6-6-6-6" />
          </svg>
        </button>
      </div>
    </div>
    <!-- The padding (taken back by the negative margins) keeps the cards' shadow inside the scrolling list, so it is
         not cut into a square. -->
    <ul
      ref="list"
      class="scrollbar-none -mx-2 mt-2 -mb-8 flex min-w-0 snap-x snap-mandatory scroll-px-2 gap-3 overflow-x-auto px-2 pt-2 pb-10"
      :class="isDragging ? 'cursor-grabbing snap-none select-none' : 'cursor-grab'"
      :style="fadeStyle"
      @scroll.passive="updateEdges"
      @pointerdown="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="onPointerUp"
      @pointercancel="onPointerUp"
    >
      <li
        v-for="(review, index) in reviews"
        :key="index"
        class="flex w-[250px] shrink-0 snap-start flex-col gap-2 rounded-card bg-surface p-4 ring-1 ring-line shadow-soft"
      >
        <p class="text-sm font-bold break-words">{{ review.firstName }}</p>
        <StarRating :value="review.rating" size="sm" />
        <p class="text-[13px] leading-snug break-words">{{ review.comment }}</p>
      </li>
    </ul>
  </section>
</template>
