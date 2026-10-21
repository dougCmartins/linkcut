<script setup lang="ts">
import { computed, useId } from 'vue'
import { useI18n } from 'vue-i18n'
import TextField from '@/components/ui/TextField.vue'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { MIN_UI_CONTRAST, contrastRatio, isHexColor } from '@/utils/color'

const props = defineProps<{
  /** Page background of the selected palette, to check contrast. Null when unknown. */
  pageColor: string | null
  /** Color used when the barber turns the custom color on. */
  initialColor: string
  error?: string
}>()
const accent = defineModel<string | null>({ required: true })
const { t } = useI18n()
const id = useId()

const isEnabled = computed({
  get: () => accent.value !== null,
  set: (enabled: boolean) => (accent.value = enabled ? props.initialColor : null),
})

const pickerValue = computed(() => (accent.value && isHexColor(accent.value) ? accent.value : props.initialColor))

const contrast = computed(() => {
  if (!accent.value || !props.pageColor || !isHexColor(accent.value)) return null
  const ratio = contrastRatio(accent.value, props.pageColor)
  return { ratio: ratio.toFixed(1).replace('.', ','), isLow: ratio < MIN_UI_CONTRAST }
})
</script>

<template>
  <div class="grid gap-4">
    <ToggleSwitch
      v-model="isEnabled"
      :label="t('settings.appearance.accentToggle')"
      :description="t('settings.appearance.accentToggleDescription')"
    />
    <template v-if="accent !== null">
      <div class="flex items-start gap-3">
        <div class="shrink-0">
          <label :for="id" class="block pl-2 text-xs font-semibold text-muted">{{ t('settings.appearance.accentPicker') }}</label>
          <input
            :id="id"
            type="color"
            :value="pickerValue"
            class="mt-1.5 h-11 w-16 cursor-pointer rounded-full bg-surface p-1 ring-1 ring-line shadow-soft"
            @input="accent = ($event.target as HTMLInputElement).value"
          />
        </div>
        <TextField
          :model-value="accent"
          class="min-w-0 flex-1"
          :label="t('settings.appearance.accentHex')"
          placeholder="#d6a740"
          :maxlength="7"
          :error="error"
          @update:model-value="accent = $event.trim()"
        />
      </div>
      <p
        v-if="contrast"
        class="rounded-card px-4 py-3 text-sm font-semibold ring-1"
        :class="contrast.isLow ? 'bg-danger/10 text-danger ring-danger/40' : 'bg-success/10 text-success ring-success/40'"
        role="status"
      >
        {{
          contrast.isLow
            ? t('settings.appearance.contrastLow', { ratio: contrast.ratio })
            : t('settings.appearance.contrastOk', { ratio: contrast.ratio })
        }}
      </p>
    </template>
  </div>
</template>
