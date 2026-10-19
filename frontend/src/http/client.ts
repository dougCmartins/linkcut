import { ApiError, isEnvelope } from './apiError'
import { ApiErrorCode } from '@/types/apiErrorCode'

/**
 * Small fetch client for the linkcut API.
 * - success: returns { data } where data is the response body (the API never wraps successes);
 * - error: throws ApiError built from the error envelope;
 * - authenticated calls send the Bearer token and, on 401, try one silent refresh before giving up.
 */

export interface HttpResponse<T> {
  data: T
}

export interface HttpOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE'
  /** JSON body, or FormData for file uploads (the browser sets the multipart header). */
  body?: unknown
  /** Sends the access token and refreshes it once on 401. Default: true. */
  auth?: boolean
}

/** Supplied by the auth store, so the client does not depend on it. */
export interface HttpAuthHandlers {
  accessToken: () => string | null
  /** Gets a new access token; resolves false when the session is over. */
  refresh: () => Promise<boolean>
  /** Called when the session cannot be recovered (e.g. go to the login). */
  onSessionExpired: () => void
}

let authHandlers: HttpAuthHandlers | null = null

export function setHttpAuthHandlers(handlers: HttpAuthHandlers | null): void {
  authHandlers = handlers
}

const baseUrl = () => (import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '')

function newRequestId(): string {
  return typeof crypto !== 'undefined' && 'randomUUID' in crypto
    ? crypto.randomUUID()
    : `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`
}

async function send<T>(path: string, options: HttpOptions): Promise<HttpResponse<T>> {
  const headers: Record<string, string> = { Accept: 'application/json', 'X-Request-Id': newRequestId() }
  const isForm = options.body instanceof FormData
  if (options.body !== undefined && !isForm) headers['Content-Type'] = 'application/json'
  const token = options.auth === false ? null : authHandlers?.accessToken()
  if (token) headers.Authorization = `Bearer ${token}`

  let response: Response
  try {
    response = await fetch(`${baseUrl()}${path}`, {
      method: options.method ?? 'GET',
      headers,
      body: options.body === undefined || isForm ? (options.body as FormData | undefined) : JSON.stringify(options.body),
    })
  } catch {
    throw ApiError.network()
  }

  if (response.status === 204) return { data: undefined as T }
  const body: unknown = await response.json().catch(() => null)
  if (!response.ok) {
    throw isEnvelope(body)
      ? new ApiError(body.code, response.status, body)
      : new ApiError(ApiErrorCode.ServerError, response.status)
  }
  return { data: body as T }
}

export async function http<T>(path: string, options: HttpOptions = {}): Promise<HttpResponse<T>> {
  try {
    return await send<T>(path, options)
  } catch (caught) {
    const canRefresh = options.auth !== false && authHandlers && caught instanceof ApiError && caught.status === 401
    if (!canRefresh) throw caught
    if (await authHandlers!.refresh()) return send<T>(path, options)
    authHandlers!.onSessionExpired()
    throw caught
  }
}
