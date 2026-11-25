import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as teamApi from './team.api'
import type { StaffMember, StaffPermissions } from './team.model'

export const useTeamStore = defineStore('team', () => {
  const members = ref<StaffMember[]>([])
  const loading = ref(false)
  const loaded = ref(false)

  const count = computed(() => members.value.length)
  const showTeamNav = computed(() => members.value.length >= 2)

  async function ensureLoaded(): Promise<void> {
    if (loaded.value || loading.value) return
    loading.value = true
    try {
      members.value = await teamApi.fetchStaff()
      loaded.value = true
    } catch {
      // The team is extra on the dashboard: without it the rest still works (no unhandled error in the browser).
      members.value = []
    } finally {
      loading.value = false
    }
  }

  function clear(): void {
    members.value = []
    loaded.value = false
  }

  async function invite(name: string, email: string): Promise<StaffMember> {
    const created = await teamApi.inviteStaff(name, email)
    members.value = [...members.value, created]
    return created
  }

  async function setActive(id: number, active: boolean): Promise<void> {
    const updated = await teamApi.updateStaffActive(id, active)
    members.value = members.value.map((m) => (m.id === id ? updated : m))
  }

  async function setPermissions(id: number, permissions: StaffPermissions): Promise<void> {
    const updated = await teamApi.updateStaffPermissions(id, permissions)
    members.value = members.value.map((m) => (m.id === id ? updated : m))
  }

  return { members, loading, loaded, count, showTeamNav, ensureLoaded, clear, invite, setActive, setPermissions }
})
