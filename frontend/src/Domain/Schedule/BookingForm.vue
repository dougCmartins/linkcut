<script setup lang="ts">
import { useI18n } from 'vue-i18n'

defineProps<{ clientName: string; clientWhatsapp: string }>()
const emit = defineEmits<{
  'update:clientName': [value: string]
  'update:clientWhatsapp': [value: string]
}>()
const { t } = useI18n()

const inputClass =
  'mt-1.5 h-12 w-full rounded-full bg-surface px-5 text-[15px] text-ink ring-1 ring-line shadow-soft placeholder:text-muted/70 focus:ring-2 focus:ring-accent focus:outline-none'
</script>

<template>
  <div class="grid gap-3">
    <label class="block">
      <span class="pl-2 text-xs font-semibold text-muted">{{ t('booking.nameLabel') }}</span>
      <input
        :value="clientName"
        type="text"
        autocomplete="name"
        :placeholder="t('booking.namePlaceholder')"
        :class="inputClass"
        @input="emit('update:clientName', ($event.target as HTMLInputElement).value)"
      />
    </label>
    <label class="block">
      <span class="pl-2 text-xs font-semibold text-muted">{{ t('booking.whatsappLabel') }}</span>
      <input
        :value="clientWhatsapp"
        type="tel"
        inputmode="tel"
        autocomplete="tel"
        :placeholder="t('booking.whatsappPlaceholder')"
        :class="inputClass"
        @input="emit('update:clientWhatsapp', ($event.target as HTMLInputElement).value)"
      />
    </label>
  </div>
</template>
