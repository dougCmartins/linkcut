import { ApiErrorCode } from '@/types/apiErrorCode'

/** The API error envelope, as thrown by the HTTP client. */
export interface ApiErrorEnvelope {
  data: Record<string, unknown> | null
  message: string
  code: string
  status_code: number
  /** Field → [failed rule], e.g. { email: ['required'] }. */
  errors: Record<string, string[]> | []
  request_id: string | null
}

export class ApiError extends Error {
  constructor(
    public readonly code: string,
    public readonly status: number,
    public readonly envelope: ApiErrorEnvelope | null = null,
  ) {
    super(envelope?.message ?? code)
    this.name = 'ApiError'
  }

  get requestId(): string | null {
    return this.envelope?.request_id ?? null
  }

  /** Extra data some errors carry (e.g. { attempts_left } for INVALID_CODE). */
  get data(): Record<string, unknown> | null {
    return this.envelope?.data ?? null
  }

  static network(): ApiError {
    return new ApiError(ApiErrorCode.NetworkError, 0)
  }
}

export function isEnvelope(body: unknown): body is ApiErrorEnvelope {
  return typeof body === 'object' && body !== null && 'code' in body && 'status_code' in body
}
