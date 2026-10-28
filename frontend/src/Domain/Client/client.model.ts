import type { AppointmentStatus } from '@/types/appointmentStatus'

/** Why a code is sent to the client's e-mail. */
export const CodePurpose = {
  /** Completes the sign-up (creates the password). */
  Registration: 'registration',
  /** "Esqueci minha senha". */
  Password: 'password',
} as const
export type CodePurpose = (typeof CodePurpose)[keyof typeof CodePurpose]

export interface ClientProfile {
  id: number
  name: string
  email: string
  phone: string
  /** Completed the sign-up (has a password). */
  hasAccount: boolean
  /** Gets the reminders and invitations by e-mail (off after the opt-out). */
  emailsEnabled: boolean
}

/** The client-area session (separate from the barber's). Times are epoch milliseconds. */
export interface ClientSession {
  accessToken: string
  refreshToken: string
  accessExpiresAt: number
  refreshExpiresAt: number
  client: ClientProfile
}

export interface CodeSent {
  /** When a new code can be asked (epoch ms). */
  resendAvailableAt: number
}

export interface BarbershopContact {
  name: string
  slug: string
  /** One line ("Rua Augusta, 1500 – Consolação, São Paulo/SP"); empty without an address. */
  address: string
  mapsUrl: string
  whatsappUrl: string
  /** Photo or logo of the barbershop (null: its initials are shown). */
  avatarUrl: string | null
}

/** A booking in the client area, at any barbershop where the client booked. */
export interface ClientAppointment {
  id: number
  serviceName: string
  /** "YYYY-MM-DD" and "HH:mm", in the barbershop's timezone. */
  date: string
  time: string
  endTime: string
  status: AppointmentStatus
  barbershop: BarbershopContact
  hasReview: boolean
}
