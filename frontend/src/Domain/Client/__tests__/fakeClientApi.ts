import { envelope, installFakeApi, type FakeCall, type FakeHandler } from '@/utils/testFetch'

/** A fake client-area API with the same contract as the backend (snake_case, error envelope). */
export const RIGHT_CODE = '654321'
export const CLIENT_PASSWORD = 'segredo123'

export const CLIENT = { id: 11, name: 'Ana Souza', email: 'ana@example.com', phone: '11912345678', has_account: true }

export function clientSessionBody(accessToken = 'client-access-1', refreshToken = 'client-refresh-1') {
  return {
    access_token: accessToken,
    token_type: 'Bearer',
    expires_in: 3600,
    refresh_token: refreshToken,
    refresh_expires_in: 2_592_000,
    client: CLIENT,
  }
}

/** Upcoming (confirmed, 2099) at Navalha and a past one (completed) at Rafael. */
export const APPOINTMENTS = [
  {
    id: 2,
    service_name: 'Barba',
    date: '2099-10-21',
    starts_at: '15:00',
    ends_at: '15:30',
    status: 'confirmed',
    barbershop: {
      name: 'Navalha',
      slug: 'navalha',
      address: 'Rua Augusta, 1500 – Consolação, São Paulo/SP',
      maps_url: 'https://www.google.com/maps/search/?api=1&query=-23.5578%2C-46.6609',
      whatsapp_url: 'https://wa.me/5511999998888',
      booking_url: 'http://localhost:5173/navalha',
      avatar_url: '/storage/images/navalha.webp',
    },
  },
  {
    id: 1,
    service_name: 'Corte',
    date: '2026-10-12',
    starts_at: '10:00',
    ends_at: '10:45',
    status: 'completed',
    barbershop: {
      name: 'Rafael Costa',
      slug: 'rafael',
      address: 'Rua Augusta, 1500 – Consolação, São Paulo/SP',
      maps_url: 'https://maps.test',
      whatsapp_url: '',
      booking_url: 'http://localhost:5173/rafael',
    },
  },
]

export function clientRoutes(): Record<string, FakeHandler> {
  let attemptsLeft = 5
  const setPassword = (call: FakeCall) => {
    if (call.body?.code === RIGHT_CODE) return { status: 201, body: clientSessionBody() }
    attemptsLeft -= 1
    return envelope('INVALID_CODE', 422, { attempts_left: attemptsLeft })
  }
  return {
    'POST /client/codes': () => ({ status: 201, body: { expires_in: 900, resend_after: 30 } }),
    'POST /client/registration': setPassword,
    'POST /client/password': setPassword,
    'POST /client/login': (call: FakeCall) =>
      call.body?.password === CLIENT_PASSWORD ? { status: 201, body: clientSessionBody() } : envelope('INVALID_CREDENTIALS', 401),
    'POST /client/token/refresh': () => ({ status: 201, body: clientSessionBody('client-access-2', 'client-refresh-2') }),
    'POST /client/logout': () => ({ status: 204 }),
    'GET /client/appointments': (call: FakeCall) =>
      call.headers.Authorization?.startsWith('Bearer client-access')
        ? { status: 200, body: APPOINTMENTS }
        : envelope('UNAUTHENTICATED', 401),
  }
}

export function installFakeClientApi(extraRoutes: Record<string, FakeHandler> = {}) {
  return installFakeApi({ ...clientRoutes(), ...extraRoutes })
}
