<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import { PushSupport, usePushStore } from './push.store'

/** Dashboard invitation to turn on the notices of new requests (until turned on or dismissed on this device). */
const DISMISSED_KEY = 'linkcut.pushPromptDismissed'

const { t } = useI18n()
const push = usePushStore()

function readDismissed(): boolean {
  try {
    return localStorage.getItem(DISMISSED_KEY) === '1'
  } catch {
    return false
  }
}

const isDismissed = ref(readDismissed())

function dismiss(): void {
  isDismissed.value = true
  try {
    localStorage.setItem(DISMISSED_KEY, '1')
  } catch {
    // Storage blocked: hidden until the page reloads.
  }
}

const isVisible = computed(
  () => !isDismissed.value && (push.canEnable || push.support === PushSupport.NeedsInstall || push.hasFailed),
)

onMounted(() => push.refresh())
</script>

<template>
  <section v-if="isVisible" class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft" aria-labelledby="push-prompt-title">
    <div class="flex items-start gap-3">
      <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-accent/15 text-accent" aria-hidden="true">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0" />
        </svg>
      </span>
      <div class="min-w-0 flex-1">
        <h2 id="push-prompt-title" class="text-base">{{ t('push.promptTitle') }}</h2>
        <p class="mt-1 text-sm text-muted">
          {{ push.support === PushSupport.NeedsInstall ? t('push.needsInstall') : t('push.promptText') }}
        </p>
        <p v-if="push.hasFailed" class="mt-2 text-sm text-danger" role="alert">{{ t('push.failed') }}</p>
      </div>
    </div>
    <div class="mt-4 flex gap-3">
      <BaseButton variant="ghost" @click="dismiss">{{ t('push.later') }}</BaseButton>
      <BaseButton v-if="push.canEnable || push.hasFailed" class="flex-1" variant="primary" elevated :disabled="push.isBusy" @click="push.enable()">
        {{ push.isBusy ? t('push.working') : t('push.enable') }}
      </BaseButton>
    </div>
  </section>
</template>
