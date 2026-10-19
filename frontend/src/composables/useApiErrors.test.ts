import { describe, expect, it } from 'vitest'
import { UNEXPECTED_ERROR_KEY, useApiErrors } from './useApiErrors'
import { ApiError } from '@/http/apiError'
import { ApiErrorCode } from '@/types/apiErrorCode'

describe('useApiErrors', () => {
  it('maps API codes to i18n keys and unknown errors to "unexpected"', () => {
    // Arrange
    const { messageKey } = useApiErrors()

    // Act
    const keys = [
      messageKey(new ApiError(ApiErrorCode.InvalidCredentials, 401)),
      messageKey(new ApiError(ApiErrorCode.InvalidRefreshToken, 401)),
      messageKey(ApiError.network()),
      messageKey(new ApiError('SOMETHING_NEW', 418)),
      messageKey(new Error('boom')),
    ]

    // Assert
    expect(keys).toEqual(['invalidCredentials', 'sessionExpired', 'network', UNEXPECTED_ERROR_KEY, UNEXPECTED_ERROR_KEY])
  })

  it('reads attempts_left of a wrong code', () => {
    // Arrange
    const { attemptsLeft } = useApiErrors()
    const error = new ApiError(ApiErrorCode.InvalidCode, 422, {
      data: { attempts_left: 3 },
      message: 'Invalid code.',
      code: ApiErrorCode.InvalidCode,
      status_code: 422,
      errors: [],
      request_id: 'r1',
    })

    // Act
    const left = [attemptsLeft(error), attemptsLeft(new ApiError(ApiErrorCode.CodeExpired, 410))]

    // Assert
    expect(left).toEqual([3, null])
  })
})
