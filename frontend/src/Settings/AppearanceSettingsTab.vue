<script setup lang="ts">
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import ChoiceChips from '@/components/ui/ChoiceChips.vue'
import AccentColorInput from './AccentColorInput.vue'
import ButtonShapePicker from './ButtonShapePicker.vue'
import ButtonStylePicker from './ButtonStylePicker.vue'
import LinkPreview from './LinkPreview.vue'
import PalettePicker from './PalettePicker.vue'
import PhoneFrame from './PhoneFrame.vue'
import SettingsCard from './SettingsCard.vue'
import { useSettingsStore } from './settings.store'
import { SettingsSection } from './settings.model'
import { useSectionErrors } from './useSectionErrors'
import { ThemeMode } from '@/types/theme'
import { cssColorToHex } from '@/utils/color'

const FALLBACK_ACCENT = '#d6a740'

const { t } = useI18n()
const store = useSettingsStore()
const { errorFor } = useSectionErrors(SettingsSection.Theme)

const theme = computed(() => store.drafts.theme)
const profile = computed(() => store.drafts.profile)

const modeOptions = [
  { value: ThemeMode.Dark, label: t('settings.appearance.dark') },
  { value: ThemeMode.Light, label: t('settings.appearance.light') },
]

// Reads the selected palette colors from core.css through a hidden, themed element.
const probe = useTemplateRef<HTMLElement>('probe')
const paletteColors = ref<{ page: string | null; accent: string | null }>({ page: null, accent: null })

function readPaletteColors(): void {
  if (!probe.value) return
  const style = getComputedStyle(probe.value)
  paletteColors.value = {
    page: cssColorToHex(style.getPropertyValue('--page')),
    accent: cssColorToHex(style.getPropertyValue('--accent')),
  }
}

onMounted(readPaletteColors)
watch(() => [theme.value.palette, theme.value.mode], readPaletteColors, { flush: 'post' })
</script>

<template>
  <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-start">
    <div class="grid gap-5">
      <span ref="probe" :data-theme="theme.palette" :data-mode="theme.mode" class="hidden" aria-hidden="true" />

      <SettingsCard :title="t('settings.appearance.modeTitle')" :description="t('settings.appearance.modeDescription')">
        <ChoiceChips v-model="theme.mode" :options="modeOptions" :label="t('settings.appearance.modeLabel')" />
      </SettingsCard>

      <SettingsCard :title="t('settings.appearance.paletteTitle')" :description="t('settings.appearance.paletteDescription')">
        <PalettePicker v-model="theme.palette" :mode="theme.mode" :label="t('settings.appearance.paletteTitle')" />
      </SettingsCard>

      <SettingsCard :title="t('settings.appearance.buttonShapeTitle')" :description="t('settings.appearance.buttonShapeDescription')">
        <ButtonShapePicker v-model="theme.buttonShape" :label="t('settings.appearance.buttonShapeTitle')" />
      </SettingsCard>

      <SettingsCard :title="t('settings.appearance.buttonStyleTitle')" :description="t('settings.appearance.buttonStyleDescription')">
        <ButtonStylePicker v-model="theme.buttonStyle" :shape="theme.buttonShape" :label="t('settings.appearance.buttonStyleTitle')" />
      </SettingsCard>

      <SettingsCard :title="t('settings.appearance.accentTitle')">
        <AccentColorInput
          v-model="theme.customAccent"
          :page-color="paletteColors.page"
          :initial-color="paletteColors.accent ?? FALLBACK_ACCENT"
          :error="errorFor('customAccent')"
        />
      </SettingsCard>

      <SettingsCard :title="t('settings.appearance.reset')" :description="t('settings.appearance.resetDescription')">
        <BaseButton variant="ghost" @click="store.resetThemeDraft()">{{ t('settings.appearance.reset') }}</BaseButton>
      </SettingsCard>
    </div>

    <div class="lg:sticky lg:top-6">
      <PhoneFrame :theme="theme">
        <LinkPreview
          :profile="profile"
          :avatar-url="store.resolveImage(profile.avatar)"
          :background-url="store.resolveImage(profile.background)"
        />
      </PhoneFrame>
    </div>
  </div>
</template>
