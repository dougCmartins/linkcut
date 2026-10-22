import { vi } from 'vitest'

/**
 * Test helper: a fake API behind global fetch.
 * Routes are "METHOD /path" → handler returning { status, body } (JSON) or { status, text } (e.g. CSV).
 * Paths may have params ("PUT /items/:id"); the query string is not part of the route.
 * Every call is recorded (method, path, query, params, headers, parsed body).
 */
export interface FakeCall {
  method: string
  /** Path with the query string, as requested. */
  path: string
  query: URLSearchParams
  params: Record<string, string>
  headers: Record<string, string>
  body: Record<string, unknown> | null
  /** Multipart uploads. */
  form: FormData | null
}

export type FakeHandler = (call: FakeCall) => { status: number; body?: unknown; text?: string }

export const TEST_API_URL = 'http://api.test/api'

export function envelope(
  code: string,
  status: number,
  data: Record<string, unknown> | null = null,
  errors: Record<string, string[]> | [] = [],
) {
  return { status, body: { data, message: code, code, status_code: status, errors, request_id: 'req-test' } }
}

function matchRoute(routes: Record<string, FakeHandler>, method: string, pathname: string) {
  for (const [key, handler] of Object.entries(routes)) {
    const [routeMethod, routePath = ''] = key.split(' ')
    if (routeMethod !== method) continue
    const names: string[] = []
    const pattern = routePath.replace(/:(\w+)/g, (_, name: string) => {
      names.push(name)
      return '([^/]+)'
    })
    const match = new RegExp(`^${pattern}$`).exec(pathname)
    if (match) return { handler, params: Object.fromEntries(names.map((name, index) => [name, match[index + 1] ?? ''])) }
  }
  return null
}

export function installFakeApi(routes: Record<string, FakeHandler>) {
  const calls: FakeCall[] = []
  vi.stubEnv('VITE_API_URL', TEST_API_URL)
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init: RequestInit = {}) => {
      const path = url.replace(TEST_API_URL, '')
      const [pathname = '', search = ''] = path.split('?')
      const method = init.method ?? 'GET'
      const route = matchRoute(routes, method, pathname)
      const call: FakeCall = {
        method,
        path,
        query: new URLSearchParams(search),
        params: route?.params ?? {},
        headers: (init.headers ?? {}) as Record<string, string>,
        body: init.body && !(init.body instanceof FormData) ? (JSON.parse(String(init.body)) as Record<string, unknown>) : null,
        form: init.body instanceof FormData ? init.body : null,
      }
      calls.push(call)
      const { status, body, text }: ReturnType<FakeHandler> = route ? route.handler(call) : envelope('NOT_FOUND', 404)
      if (text !== undefined) return new Response(text, { status, headers: { 'Content-Type': 'text/csv; charset=UTF-8' } })
      return new Response(status === 204 ? null : JSON.stringify(body ?? null), {
        status,
        headers: { 'Content-Type': 'application/json' },
      })
    }),
  )
  return { calls }
}
