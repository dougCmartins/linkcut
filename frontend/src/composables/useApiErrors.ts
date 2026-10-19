import { ApiError } from '@/http/apiError'
import { ApiErrorCode } from '@/types/apiErrorCode'

/** API error code → i18n key under `errors.api`. Anything unknown is "unexpected". */
const MESSAGE_KEYS: Partial<Record<string, string>> = {
  [ApiErrorCode.NetworkError]: 'network',
  [ApiErrorCode.TooManyRequests]: 'tooManyRequests',
  [ApiErrorCode.InvalidCredentials]: 'invalidCredentials',
  [ApiErrorCode.ChallengeExpired]: 'challengeExpired',
  [ApiErrorCode.ResendTooSoon]: 'resendTooSoon',
  [ApiErrorCode.SendLimitReached]: 'sendLimitReached',
  [ApiErrorCode.ChannelUnavailable]: 'channelUnavailable',
  [ApiErrorCode.InvalidCode]: 'invalidCode',
  [ApiErrorCode.CodeExpired]: 'codeExpired',
  [ApiErrorCode.TooManyAttempts]: 'tooManyAttempts',
  [ApiErrorCode.InvalidRefreshToken]: 'sessionExpired',
  [ApiErrorCode.Unauthenticated]: 'sessionExpired',
  [ApiErrorCode.TenantInactive]: 'tenantInactive',
  [ApiErrorCode.ImageNotFound]: 'imageNotFound',
  [ApiErrorCode.InvalidImage]: 'invalidImage',
}

export const UNEXPECTED_ERROR_KEY = 'unexpected'

/** Shared reading of the API error envelope (never parse it inside a component). */
export function useApiErrors() {
  function errorCode(caught: unknown): string {
    return caught instanceof ApiError ? caught.code : ApiErrorCode.NetworkError
  }

  function messageKey(caught: unknown): string {
    return (caught instanceof ApiError && MESSAGE_KEYS[caught.code]) || UNEXPECTED_ERROR_KEY
  }

  function hasErrorCode(caught: unknown, code: ApiErrorCode): boolean {
    return caught instanceof ApiError && caught.code === code
  }

  /** attempts_left of an INVALID_CODE error, or null. */
  function attemptsLeft(caught: unknown): number | null {
    const value = caught instanceof ApiError ? caught.data?.attempts_left : null
    return typeof value === 'number' ? value : null
  }

  function requestId(caught: unknown): string | null {
    return caught instanceof ApiError ? caught.requestId : null
  }

  return { errorCode, messageKey, hasErrorCode, attemptsLeft, requestId }
}
