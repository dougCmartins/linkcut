export const AppointmentStatus = {
  Pending: 'pending',
  Confirmed: 'confirmed',
  Rejected: 'rejected',
  /** Service done: its value went to the history/revenue. */
  Completed: 'completed',
} as const

export type AppointmentStatus = (typeof AppointmentStatus)[keyof typeof AppointmentStatus]
