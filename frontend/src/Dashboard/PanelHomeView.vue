<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { isTeamBarber } from '@/Domain/Identity/roles'

/** /painel: the owner's dashboard, or the agenda of a barber of the team (docs/decisoes/0001 P3). */
const DashboardView = defineAsyncComponent(() => import('./DashboardView.vue'))
const BarberHomeView = defineAsyncComponent(() => import('./BarberHomeView.vue'))
const authStore = useAuthStore()
const isBarber = computed(() => isTeamBarber(authStore.user))
</script>

<template>
  <BarberHomeView v-if="isBarber" />
  <DashboardView v-else />
</template>
