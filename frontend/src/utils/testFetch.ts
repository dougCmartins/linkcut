import { vi } from 'vitest'

/**
 * Test helper: a fake API behind global fetch.
 * Routes are "METHOD /path" → handler returning { status, body }.
 * Every call is recorded (method, path, headers, parsed body).
 */
export interface FakeCall {
  method: string
  path: string
  headers: Record<string, string>
  body: Record<string, unknown> | null
  /** Multipart uploads. */
  form: FormData | null
}

export type FakeHandler = (call: FakeCall) => { status: number; body?: unknown }

export const TEST_API_URL = 'http://api.test/api'

export function envelope(code: string, status: number, data: Record<string, unknown> | null = null) {
  return { status, body: { data, message: code, code, status_code: status, errors: [], request_id: 'req-test' } }
}

export function installFakeApi(routes: Record<string, FakeHandler>) {
  const calls: FakeCall[] = []
  vi.stubEnv('VITE_API_URL', TEST_API_URL)
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init: RequestInit = {}) => {
      const path = url.replace(TEST_API_URL, '')
      const call: FakeCall = {
        method: init.method ?? 'GET',
        path,
        headers: (init.headers ?? {}) as Record<string, string>,
        body: init.body && !(init.body instanceof FormData) ? (JSON.parse(String(init.body)) as Record<string, unknown>) : null,
        form: init.body instanceof FormData ? init.body : null,
      }
      calls.push(call)
      const handler = routes[`${call.method} ${path}`]
      const { status, body } = handler ? handler(call) : envelope('NOT_FOUND', 404)
      return new Response(status === 204 ? null : JSON.stringify(body ?? null), {
        status,
        headers: { 'Content-Type': 'application/json' },
      })
    }),
  )
  return { calls }
}
