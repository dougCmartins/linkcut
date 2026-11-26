<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import HorizontalScroller from '@/components/ui/HorizontalScroller.vue'
import type { PublicStaff } from './booking.model'

/**
 * "Escolha o barbeiro": "Qualquer um disponível" (the default, null) and one card per barber.
 * While the list for the chosen services loads, placeholders keep the row's height (no layout shift).
 */
defineProps<{ staff: PublicStaff[]; isLoading: boolean; label: string }>()
const selected = defineModel<number | null>({ required: true })
const { t } = useI18n()

const ANY_KEY = 'any'
const initial = (name: string) => name.trim().charAt(0).toUpperCase()
const chipClass = (isOn: boolean) =>
  isOn ? 'bg-accent text-on-accent ring-accent shadow-soft' : 'bg-surface text-ink ring-line shadow-soft hover:-translate-y-0.5'
</script>

<template>
  <div v-if="isLoading" class="flex gap-2 pt-2 pb-2" aria-hidden="true">
    <span v-for="index in Math.max(2, staff.length + 1)" :key="index" class="h-12 w-32 shrink-0 animate-pulse rounded-button bg-raised" />
  </div>
  <HorizontalScroller
    v-else
    :label="label"
    :selected-key="selected === null ? ANY_KEY : String(selected)"
    class="-mx-2 -mb-6 gap-2 px-2.5 pt-2 pb-8"
  >
    <button
      :key="ANY_KEY"
      type="button"
      role="radio"
      :aria-checked="selected === null"
      class="flex h-12 shrink-0 snap-start items-center gap-2 rounded-button px-4 text-sm font-bold ring-1 transition duration-150"
      :class="chipClass(selected === null)"
      @click="selected = null"
    >
      {{ t('booking.staff.any') }}
    </button>
    <button
      v-for="member in staff"
      :key="String(member.id)"
      type="button"
      role="radio"
      :aria-checked="selected === member.id"
      class="flex h-12 shrink-0 snap-start items-center gap-2 rounded-button pr-4 pl-1.5 text-sm font-bold ring-1 transition duration-150"
      :class="chipClass(selected === member.id)"
      @click="selected = member.id"
    >
      <img v-if="member.avatar" :src="member.avatar" alt="" class="size-9 rounded-full object-cover" />
      <span v-else class="flex size-9 items-center justify-center rounded-full bg-raised text-ink" aria-hidden="true">{{
        initial(member.name)
      }}</span>
      {{ member.name }}
    </button>
  </HorizontalScroller>
</template>
