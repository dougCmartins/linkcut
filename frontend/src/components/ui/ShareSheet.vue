<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ShareTarget, displayUrl, shareUrl } from '@/utils/share'
import SmartImage from './SmartImage.vue'

/**
 * "Compartilhar": a card of the page (photo, name, link) and the ways to send it: copy, WhatsApp, Facebook, X,
 * Telegram and, on phones, the system sheet ("Mais opções": Instagram, Messenger...).
 */
const props = withDefaults(defineProps<{ url: string; name: string; imageUrl?: string | null; title?: string }>(), {
  imageUrl: null,
  title: undefined,
})
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()

const COPIED_FOR_MS = 2000
const dialog = ref<HTMLElement | null>(null)
const copied = ref(false)
let copiedTimer: ReturnType<typeof setTimeout> | undefined

const message = computed(() => t('share.message', { name: props.name }))
const canUseSystemSheet = typeof navigator !== 'undefined' && typeof navigator.share === 'function'
const TARGETS = [
  {
    id: ShareTarget.WhatsApp,
    label: 'WhatsApp',
    tone: 'bg-[#25d366] text-white',
    icon: 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.5-.6-2.6-1.1-4.3-3.8-4.4-4-.1-.2-1.1-1.4-1.1-2.7 0-1.3.7-1.9.9-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.3 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.1.3.6 1.1 1.4 1.8 1 .9 1.8 1.1 2.1 1.3.3.1.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.8.9c.3.1.4.2.5.3.1.2.1.7-.1 1.3Z',
  },
  {
    id: ShareTarget.Facebook,
    label: 'Facebook',
    tone: 'bg-[#1877f2] text-white',
    icon: 'M14 13.5h2.5l1-4H14v-2c0-1 .5-2 2-2h1.5V2.1A20 20 0 0 0 14.8 2C12 2 10 3.7 10 6.8v2.7H7v4h3V22h4v-8.5Z',
  },
  { id: ShareTarget.X, label: 'X', tone: 'bg-black text-white', icon: 'M17.8 3h3.1l-6.8 7.7L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L2 3h6.4l4.4 5.8L17.8 3Zm-1.1 16.2h1.7L7.4 4.7H5.6l11.1 14.5Z' },
  {
    id: ShareTarget.Telegram,
    label: 'Telegram',
    tone: 'bg-[#29a9eb] text-white',
    icon: 'M21.9 4.3 18.7 19.5c-.2 1-.9 1.3-1.7.8l-4.8-3.6-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.6 13.3 1.9 11.8c-1-.3-1-1 .2-1.5L20.6 3.2c.9-.3 1.6.2 1.3 1.1Z',
  },
] as const

async function copy(): Promise<void> {
  try {
    await navigator.clipboard.writeText(props.url)
    copied.value = true
    clearTimeout(copiedTimer)
    copiedTimer = setTimeout(() => (copied.value = false), COPIED_FOR_MS)
  } catch {
    copied.value = false
  }
}

async function shareWithSystem(): Promise<void> {
  try {
    await navigator.share({ title: props.name, text: message.value, url: props.url })
  } catch {
    // Closed by the person: nothing to do.
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') emit('close')
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', onKeydown)
  dialog.value?.focus()
})
onBeforeUnmount(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKeydown)
  clearTimeout(copiedTimer)
})

const actionClass = 'flex flex-col items-center gap-1.5 text-xs font-semibold text-ink'
const bubbleClass = 'flex size-14 items-center justify-center rounded-full shadow-soft'
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" aria-hidden="true" @click="emit('close')" />
    <section
      ref="dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="share-title"
      tabindex="-1"
      class="relative w-full max-w-md rounded-t-[28px] bg-page px-5 pt-3 pb-8 shadow-float ring-1 ring-line outline-none sm:rounded-[28px] sm:pt-5"
    >
      <span class="mx-auto mb-3 block h-1.5 w-12 rounded-full bg-line sm:hidden" aria-hidden="true" />
      <header class="flex items-center justify-between">
        <h2 id="share-title" class="text-xl">{{ title ?? t('share.title') }}</h2>
        <button
          type="button"
          :aria-label="t('share.close')"
          class="flex size-10 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft"
          @click="emit('close')"
        >
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </header>

      <div class="mt-5 flex flex-col items-center rounded-[24px] bg-accent px-5 py-7 text-center text-on-accent shadow-soft">
        <div class="size-20 overflow-hidden rounded-full ring-4 ring-on-accent/30">
          <SmartImage :src="imageUrl ?? ''" alt="" class="size-full object-cover" />
        </div>
        <p class="mt-3 font-display text-xl font-extrabold break-words">{{ name }}</p>
        <p class="mt-1 text-sm font-semibold [overflow-wrap:anywhere] opacity-80">{{ displayUrl(url) }}</p>
      </div>

      <div class="mt-6 grid grid-cols-3 gap-y-5 sm:grid-cols-6">
        <button type="button" :class="actionClass" @click="copy">
          <span :class="[bubbleClass, 'bg-raised ring-1 ring-line']">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" />
            </svg>
          </span>
          <span>{{ copied ? t('share.copied') : t('share.copy') }}</span>
        </button>
        <a
          v-for="target in TARGETS"
          :key="target.id"
          :href="shareUrl(target.id, url, message)"
          target="_blank"
          rel="noopener noreferrer"
          :class="actionClass"
          :aria-label="t('share.via', { network: target.label })"
        >
          <span :class="[bubbleClass, target.tone]">
            <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="target.icon" /></svg>
          </span>
          <span aria-hidden="true">{{ target.label }}</span>
        </a>
        <button v-if="canUseSystemSheet" type="button" :class="actionClass" @click="shareWithSystem">
          <span :class="[bubbleClass, 'bg-raised ring-1 ring-line']">
            <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <circle cx="5" cy="12" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="19" cy="12" r="2" />
            </svg>
          </span>
          <span>{{ t('share.more') }}</span>
        </button>
      </div>
      <p class="sr-only" role="status">{{ copied ? t('share.copied') : '' }}</p>
    </section>
  </div>
</template>
