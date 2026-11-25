<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { SLUG_MAX_LENGTH, slugify } from './slug'
import type { SlugStatus } from './useSlugCheck'

/** The public link input ("linkcut.app/<slug>") with its availability line and the "Usar …" suggestion. */
const props = defineProps<{ id: string; label: string; status: SlugStatus; suggestion: string }>()
const emit = defineEmits<{ edit: [slug: string] }>()
const slug = defineModel<string>({ required: true })
const { t } = useI18n()

/** Where the public page will live, e.g. "linkcut.app/". */
const publicHost = `${window.location.host}/`
const message = computed(() => (props.status === 'idle' ? '' : t(`signUp.slug.${props.status}`)))

function edit(value: string): void {
  slug.value = slugify(value)
  emit('edit', slug.value)
}
</script>

<template>
  <div>
    <label :for="id" class="block pl-2 text-xs font-semibold text-muted">{{ label }}</label>
    <div
      class="mt-1.5 flex h-11 items-center rounded-full bg-surface pl-5 ring-1 ring-line shadow-soft focus-within:ring-2 focus-within:ring-accent"
    >
      <span class="shrink-0 text-base text-muted" aria-hidden="true">{{ publicHost }}</span>
      <input
        :id="id"
        :value="slug"
        class="h-full min-w-0 flex-1 rounded-r-full bg-transparent pr-5 text-base text-ink focus:outline-none"
        autocomplete="off"
        autocapitalize="none"
        spellcheck="false"
        :maxlength="SLUG_MAX_LENGTH"
        :aria-describedby="`${id}-status`"
        @input="edit(($event.target as HTMLInputElement).value)"
      />
    </div>
    <p
      :id="`${id}-status`"
      class="mt-1.5 pl-2 text-xs font-semibold"
      :class="status === 'available' ? 'text-success' : status === 'checking' ? 'text-muted' : 'text-danger'"
      role="status"
    >
      {{ message }}
      <button v-if="suggestion" type="button" class="ml-1 text-accent underline" @click="edit(suggestion)">
        {{ t('signUp.slug.use', { slug: suggestion }) }}
      </button>
    </p>
  </div>
</template>
