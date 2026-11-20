<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchStaff } from './team.api'

/**
 * The way into "Minha equipe" on the owner's dashboard: "Minha equipe (n)" from the 2nd barber on; before that a small
 * "Adicionar barbeiro" (a barber on their own sees no team screen until adding someone).
 */
const { t } = useI18n()
const count = ref<number | null>(null)

onMounted(async () => {
  try {
    count.value = (await fetchStaff()).length
  } catch {
    count.value = null
  }
})

const hasTeam = computed(() => (count.value ?? 0) >= 2)
</script>

<template>
  <RouterLink
    v-if="count !== null"
    to="/painel/equipe"
    class="mt-4 flex items-center justify-between rounded-card bg-surface px-4 py-3 text-sm font-semibold ring-1 ring-line shadow-soft transition hover:-translate-y-0.5"
  >
    <span>{{ hasTeam ? `${t('team.dashboardLink')} (${count})` : `+ ${t('team.dashboardAdd')}` }}</span>
    <span aria-hidden="true">→</span>
  </RouterLink>
</template>
