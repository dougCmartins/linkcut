<script setup lang="ts">
import HorizontalScroller from './HorizontalScroller.vue'

export interface TabItem {
  id: string
  label: string
  /** Shows a dot (e.g. unsaved changes). */
  hasBadge?: boolean
}

const props = defineProps<{ tabs: TabItem[]; idPrefix: string; label: string }>()
const activeTab = defineModel<string>({ required: true })

function select(index: number): void {
  const count = props.tabs.length
  const tab = props.tabs[(index + count) % count]
  if (!tab) return
  activeTab.value = tab.id
  document.getElementById(`${props.idPrefix}-tab-${tab.id}`)?.focus()
}

function onKeydown(event: KeyboardEvent): void {
  const current = props.tabs.findIndex((tab) => tab.id === activeTab.value)
  const moves: Record<string, number> = {
    ArrowRight: current + 1,
    ArrowLeft: current - 1,
    Home: 0,
    End: props.tabs.length - 1,
  }
  const target = moves[event.key]
  if (target === undefined) return
  event.preventDefault()
  select(target)
}
</script>

<template>
  <HorizontalScroller
    role="tablist"
    :label="label"
    :selected-key="activeTab"
    class="gap-1 rounded-full bg-surface p-1 ring-1 ring-line shadow-soft"
    @keydown="onKeydown"
  >
    <button
      v-for="tab in tabs"
      :id="`${idPrefix}-tab-${tab.id}`"
      :key="tab.id"
      type="button"
      role="tab"
      :aria-selected="tab.id === activeTab"
      :aria-controls="`${idPrefix}-panel-${tab.id}`"
      :tabindex="tab.id === activeTab ? 0 : -1"
      class="relative flex h-10 shrink-0 grow items-center justify-center gap-1.5 rounded-full px-4 font-display text-sm font-extrabold tracking-tight transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :class="tab.id === activeTab ? 'bg-accent text-on-accent shadow-soft' : 'text-muted hover:text-ink'"
      @click="activeTab = tab.id"
    >
      {{ tab.label }}
      <span
        v-if="tab.hasBadge"
        class="size-1.5 rounded-full"
        :class="tab.id === activeTab ? 'bg-on-accent' : 'bg-accent'"
        aria-hidden="true"
      />
    </button>
  </HorizontalScroller>
</template>
