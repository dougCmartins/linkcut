export const AppointmentStatus = {
  Pending: 'pending',
  Confirmed: 'confirmed',
  Rejected: 'rejected',
  /** Service done: its value went to the history/revenue. */
  Completed: 'completed',
  /** The client cancelled from the client area: the time is free again. */
  Cancelled: 'cancelled',
} as const

export type AppointmentStatus = (typeof AppointmentStatus)[keyof typeof AppointmentStatus]
