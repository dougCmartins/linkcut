import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from './staffSchedule.api'
import type { NewStaffTimeBlock, StaffDayHours, StaffTimeBlock } from './staffSchedule.model'

const same = (a: unknown, b: unknown) => JSON.stringify(a) === JSON.stringify(b)

/** The agenda of one barber the owner is editing: hours and services (saved together) and the blocks. */
export const useStaffScheduleStore = defineStore('staffSchedule', () => {
  const staffId = ref<number | null>(null)
  const hours = ref<StaffDayHours[]>([])
  const savedHours = ref<StaffDayHours[]>([])
  const serviceIds = ref<number[]>([])
  const savedServiceIds = ref<number[]>([])
  const blocks = ref<StaffTimeBlock[]>([])

  const isHoursDirty = computed(() => !same(hours.value, savedHours.value))
  const isServicesDirty = computed(() => !same([...serviceIds.value].sort(), [...savedServiceIds.value].sort()))
  const isDirty = computed(() => isHoursDirty.value || isServicesDirty.value)
  /** No open day or no service: the client never sees a time with this barber. */
  const isAgendaClosed = computed(() => !hours.value.some((day) => day.isOpen))
  const hasNoService = computed(() => serviceIds.value.length === 0)

  async function load(id: number): Promise<void> {
    staffId.value = id
    const [loadedHours, loadedServices, loadedBlocks] = await Promise.all([
      api.fetchStaffHours(id),
      api.fetchStaffServices(id),
      api.fetchStaffBlocks(id),
    ])
    hours.value = loadedHours
    savedHours.value = loadedHours
    serviceIds.value = loadedServices
    savedServiceIds.value = loadedServices
    blocks.value = loadedBlocks
  }

  /** "Salvar": only what changed goes to the API. */
  async function save(): Promise<void> {
    const id = staffId.value
    if (id === null) return
    if (isHoursDirty.value) {
      const saved = await api.saveStaffHours(id, hours.value)
      hours.value = saved
      savedHours.value = saved
    }
    if (isServicesDirty.value) {
      const saved = await api.saveStaffServices(id, serviceIds.value)
      serviceIds.value = saved
      savedServiceIds.value = saved
    }
  }

  function discard(): void {
    hours.value = savedHours.value
    serviceIds.value = savedServiceIds.value
  }

  async function addBlock(block: NewStaffTimeBlock): Promise<void> {
    if (staffId.value === null) return
    const created = await api.createStaffBlock(staffId.value, block)
    blocks.value = [...blocks.value, created]
  }

  async function removeBlock(blockId: number): Promise<void> {
    if (staffId.value === null) return
    await api.deleteStaffBlock(staffId.value, blockId)
    blocks.value = blocks.value.filter((block) => block.id !== blockId)
  }

  return {
    staffId,
    hours,
    serviceIds,
    blocks,
    isDirty,
    isAgendaClosed,
    hasNoService,
    load,
    save,
    discard,
    addBlock,
    removeBlock,
  }
})
