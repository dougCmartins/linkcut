import type { SessionUser } from './auth.model'

/** A barber of the team (not the owner): sees only their agenda, never finance, settings or the team. */
export function isTeamBarber(user: SessionUser | null | undefined): boolean {
  return user?.role === 'barber'
}
