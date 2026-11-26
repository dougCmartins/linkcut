<script setup lang="ts">
import { useI18n } from 'vue-i18n'

/** Who attends an appointment on the owner's agenda, and "Passar para outro barbeiro" while it is not done. */
defineProps<{ name: string | null; canReassign: boolean; client: string }>()
const emit = defineEmits<{ reassign: [] }>()
const { t } = useI18n()
</script>

<template>
  <div class="mt-3 flex items-center justify-between gap-3 border-t border-line pt-3 text-sm">
    <p class="min-w-0 truncate text-muted">{{ t('dashboard.reassign.with', { name: name ?? t('dashboard.reassign.nobody') }) }}</p>
    <button
      v-if="canReassign"
      type="button"
      class="shrink-0 font-bold text-accent"
      :aria-label="t('dashboard.reassign.openFor', { client })"
      @click="emit('reassign')"
    >
      {{ t('dashboard.reassign.open') }}
    </button>
  </div>
</template>
