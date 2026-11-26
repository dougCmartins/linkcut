import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/Domain/Identity/auth.store'
import { useTeamStore } from '@/Domain/Team/team.store'
import type { StaffMember } from '@/Domain/Team/team.model'
import { AppointmentStatus } from '@/types/appointmentStatus'
import type { BookingRequest } from './booking.model'

const FILTER_KEY = 'linkcut.agenda-staff'

function readFilter(): number | null {
  try {
    const raw = sessionStorage.getItem(FILTER_KEY)
    return raw ? Number(raw) : null
  } catch {
    return null
  }
}

function writeFilter(staffId: number | null): void {
  try {
    if (staffId === null) sessionStorage.removeItem(FILTER_KEY)
    else sessionStorage.setItem(FILTER_KEY, String(staffId))
  } catch {
    // Storage blocked: the filter lasts while the page is open.
  }
}

/**
 * The team on the owner's agenda, from 2 active barbers on: who attends each appointment, the "Barbeiro" filter
 * (kept for the browser session only) and who can take an appointment. A barber never sees any of it.
 */
export function useAgendaTeam() {
  const auth = useAuthStore()
  const team = useTeamStore()

  const activeStaff = computed(() => team.members.filter((member) => member.active))
  const showsTeam = computed(() => auth.user?.role === 'owner' && activeStaff.value.length >= 2)
  const filterStaffId = ref<number | null>(readFilter())
  watch(filterStaffId, writeFilter)

  function staffNameOf(request: BookingRequest): string | null {
    return team.members.find((member) => member.id === request.staffId)?.name ?? null
  }

  function visible(list: BookingRequest[]): BookingRequest[] {
    const staffId = filterStaffId.value
    return showsTeam.value && staffId !== null ? list.filter((request) => request.staffId === staffId) : list
  }

  /** Only an appointment not done yet (pending or confirmed) moves to another barber. */
  function canReassign(request: BookingRequest): boolean {
    return (
      showsTeam.value && (request.status === AppointmentStatus.Pending || request.status === AppointmentStatus.Confirmed)
    )
  }

  function reassignTargets(request: BookingRequest): StaffMember[] {
    return activeStaff.value.filter((member) => member.id !== request.staffId)
  }

  return { activeStaff, showsTeam, filterStaffId, staffNameOf, visible, canReassign, reassignTargets }
}
