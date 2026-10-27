<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FeaturedReview } from '@/Domain/Settings/settings.model'
import StarRating from './StarRating.vue'

defineProps<{ reviews: FeaturedReview[] }>()
const { t } = useI18n()

const DRAG_THRESHOLD_PX = 6

const list = useTemplateRef<HTMLElement>('list')
const isDragging = ref(false)

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

onMounted(() => list.value?.addEventListener('wheel', onWheel, { passive: false }))
onBeforeUnmount(() => list.value?.removeEventListener('wheel', onWheel))
</script>

<template>
  <section aria-labelledby="reviews-carousel-title">
    <h2 id="reviews-carousel-title" class="text-lg font-extrabold">{{ t('reviews.carouselTitle') }}</h2>
    <ul
      ref="list"
      class="scrollbar-none mt-4 flex w-full min-w-0 snap-x snap-mandatory gap-3 overflow-x-auto"
      :class="isDragging ? 'cursor-grabbing snap-none select-none' : 'cursor-grab'"
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
