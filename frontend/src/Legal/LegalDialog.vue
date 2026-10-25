<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import LegalSections from './LegalSections.vue'
import { useLegalDocument, type LegalDocument } from './useLegalDocument'

/**
 * The Privacy Policy or the Terms of Use over the current screen: reading them never leaves the booking or the
 * sign-up (a new tab in Instagram/WhatsApp browsers closes everything when it is closed).
 */
const props = defineProps<{ document: LegalDocument }>()
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
const legal = useLegalDocument(() => props.document)
const dialog = ref<HTMLElement | null>(null)

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') emit('close')
}

let previousOverflow = ''
onMounted(() => {
  previousOverflow = document.body.style.overflow
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', onKeydown)
  dialog.value?.focus()
})
onBeforeUnmount(() => {
  document.body.style.overflow = previousOverflow
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <section
      ref="dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="legal-dialog-title"
      tabindex="-1"
      class="fixed inset-0 z-[60] overflow-y-auto bg-page outline-none"
    >
      <header class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-line bg-page/95 px-5 py-3 backdrop-blur">
        <h1 id="legal-dialog-title" class="text-lg leading-tight">{{ legal.title.value || t('legal.loading') }}</h1>
        <button
          type="button"
          :aria-label="t('legal.close')"
          class="flex size-10 shrink-0 items-center justify-center rounded-full bg-surface ring-1 ring-line shadow-soft"
          @click="emit('close')"
        >
          <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </header>
      <div v-if="legal.isLoaded.value" class="mx-auto w-full max-w-2xl px-5 pt-4 pb-16">
        <p class="text-xs text-muted">{{ legal.updated.value }}</p>
        <p class="mt-4 leading-relaxed text-muted">{{ legal.intro.value }}</p>
        <LegalSections :sections="legal.sections.value" id-prefix="legal-dialog" />
      </div>
    </section>
  </Teleport>
</template>
