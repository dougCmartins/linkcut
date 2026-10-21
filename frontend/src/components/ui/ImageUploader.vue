<script setup lang="ts">
import { computed, ref, useId, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import SmartImage from './SmartImage.vue'
import { ACCEPTED_IMAGE_TYPES, validateImageFile, type ImageFileError } from '@/utils/image'

type UploaderShape = 'circle' | 'wide' | 'square'

const props = withDefaults(
  defineProps<{
    label: string
    /** Resolved URL to preview, or empty. */
    src: string
    alt?: string
    shape?: UploaderShape
    isBusy?: boolean
    removable?: boolean
    /** Upload refused or not displayed (shown under the field). */
    error?: string
  }>(),
  { alt: '', shape: 'wide', isBusy: false, removable: true, error: undefined },
)

const emit = defineEmits<{ select: [file: File]; remove: [] }>()
const { t } = useI18n()
const id = useId()
const input = useTemplateRef<HTMLInputElement>('input')
const fileError = ref<ImageFileError | null>(null)

const SHAPE_CLASSES: Record<UploaderShape, string> = {
  circle: 'size-24 rounded-full',
  wide: 'aspect-[16/9] w-full rounded-card',
  square: 'aspect-square w-full rounded-card',
}

const accept = ACCEPTED_IMAGE_TYPES.join(',')
const hasImage = computed(() => Boolean(props.src))

function openPicker(): void {
  input.value?.click()
}

function onChange(event: Event): void {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  target.value = ''
  if (!file) return
  fileError.value = validateImageFile(file)
  if (!fileError.value) emit('select', file)
}
</script>

<template>
  <div :class="shape === 'circle' ? 'flex items-center gap-4' : ''">
    <p :id="`${id}-label`" class="pl-2 text-xs font-semibold text-muted" :class="shape === 'circle' ? 'sr-only' : 'mb-1.5'">
      {{ label }}
    </p>
    <div class="relative shrink-0 overflow-hidden bg-raised ring-1 ring-line shadow-soft" :class="SHAPE_CLASSES[shape]">
      <SmartImage v-if="hasImage" :src="src" :alt="alt" class="size-full object-cover" />
      <div v-else class="flex size-full items-center justify-center text-muted" aria-hidden="true">
        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="3" y="4" width="18" height="16" rx="3" />
          <circle cx="9" cy="10" r="2" />
          <path d="m21 16-5-5-9 9" />
        </svg>
      </div>
      <div v-if="isBusy" class="absolute inset-0 flex items-center justify-center bg-page/70" role="status">
        <span class="size-6 animate-spin rounded-full border-2 border-accent border-t-transparent" />
        <span class="sr-only">{{ t('settings.image.uploading') }}</span>
      </div>
    </div>

    <div :class="shape === 'circle' ? 'min-w-0' : 'mt-2.5'">
      <p v-if="shape === 'circle'" class="mb-2 text-sm font-semibold" aria-hidden="true">{{ label }}</p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          :aria-describedby="`${id}-label`"
          :disabled="isBusy"
          class="h-9 rounded-full bg-surface px-4 text-xs font-bold ring-1 ring-line shadow-soft transition hover:-translate-y-0.5 disabled:opacity-50"
          @click="openPicker"
        >
          {{ hasImage ? t('settings.image.replace') : t('settings.image.upload') }}
        </button>
        <button
          v-if="hasImage && removable"
          type="button"
          :aria-describedby="`${id}-label`"
          :disabled="isBusy"
          class="h-9 rounded-full px-4 text-xs font-bold text-danger ring-1 ring-danger/40 transition hover:bg-danger/10 disabled:opacity-50"
          @click="emit('remove')"
        >
          {{ t('settings.image.remove') }}
        </button>
      </div>
      <p v-if="fileError" class="mt-1.5 pl-1 text-xs text-danger" role="alert">
        {{ t(`settings.image.errors.${fileError}`) }}
      </p>
      <p v-else-if="error" class="mt-1.5 pl-1 text-xs text-danger" role="alert">{{ error }}</p>
      <p v-else class="mt-1.5 pl-1 text-xs text-muted">{{ t('settings.image.hint') }}</p>
    </div>

    <input ref="input" type="file" :accept="accept" class="sr-only" tabindex="-1" aria-hidden="true" @change="onChange" />
  </div>
</template>
