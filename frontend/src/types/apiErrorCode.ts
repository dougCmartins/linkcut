/** Error codes of the API error envelope ({ data, message, code, status_code, errors, request_id }). */
export const ApiErrorCode = {
  ValidationFailed: 'VALIDATION_FAILED',
  Unauthenticated: 'UNAUTHENTICATED',
  TooManyRequests: 'TOO_MANY_REQUESTS',
  NotFound: 'NOT_FOUND',
  ServerError: 'SERVER_ERROR',
  /** No answer from the API (offline, CORS, server down). Created by the SPA, not the API. */
  NetworkError: 'NETWORK_ERROR',
  InvalidCredentials: 'INVALID_CREDENTIALS',
  ChallengeExpired: 'CHALLENGE_EXPIRED',
  ResendTooSoon: 'RESEND_TOO_SOON',
  SendLimitReached: 'SEND_LIMIT_REACHED',
  ChannelUnavailable: 'CHANNEL_UNAVAILABLE',
  InvalidCode: 'INVALID_CODE',
  CodeExpired: 'CODE_EXPIRED',
  TooManyAttempts: 'TOO_MANY_ATTEMPTS',
  InvalidRefreshToken: 'INVALID_REFRESH_TOKEN',
  TenantInactive: 'TENANT_INACTIVE',
  TenantNotResolved: 'TENANT_NOT_RESOLVED',
  TenantNotFound: 'TENANT_NOT_FOUND',
  ImageNotFound: 'IMAGE_NOT_FOUND',
  InvalidImage: 'INVALID_IMAGE',
  ExpenseNotFound: 'EXPENSE_NOT_FOUND',
  SlotUnavailable: 'SLOT_UNAVAILABLE',
  InvalidStatusTransition: 'INVALID_STATUS_TRANSITION',
  BookingRequestNotFound: 'BOOKING_REQUEST_NOT_FOUND',
  BarbershopAddressMissing: 'BARBERSHOP_ADDRESS_MISSING',
  SlugUnavailable: 'SLUG_UNAVAILABLE',
  EmailAlreadyRegistered: 'EMAIL_ALREADY_REGISTERED',
} as const

export type ApiErrorCode = (typeof ApiErrorCode)[keyof typeof ApiErrorCode]
