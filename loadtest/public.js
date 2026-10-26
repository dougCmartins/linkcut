import http from 'k6/http'
import { check, sleep } from 'k6'

// A visitor from Instagram: opens a barbershop's page (profile + booking options in parallel), then the free
// times for a service; 1 in 10 asks for a time. Each virtual user has its own IP (as different phones).
const BASE = __ENV.BASE || 'http://127.0.0.1:8081'
const TENANTS = 200

export const options = {
  scenarios: {
    visitors: {
      executor: 'ramping-vus',
      stages: [
        { duration: '20s', target: 50 },
        { duration: '40s', target: 50 },
        { duration: '20s', target: 200 },
        { duration: '40s', target: 200 },
        { duration: '10s', target: 0 },
      ],
    },
  },
  thresholds: {
    'http_req_duration{page:public}': ['p(95)<800'],
    'http_req_duration{page:availability}': ['p(95)<1000'],
    http_req_failed: ['rate<0.01'],
  },
}

export default function () {
  const ip = `10.${(__VU >> 8) & 255}.${__VU & 255}.${__ITER % 250}`
  const headers = { 'X-Forwarded-For': ip, Accept: 'application/json' }
  const slug = `perf-${Math.floor(Math.random() * TENANTS)}`

  const [page, booking] = http.batch([
    ['GET', `${BASE}/api/public/${slug}`, null, { headers, tags: { page: 'public' } }],
    ['GET', `${BASE}/api/public/${slug}/booking-options`, null, { headers, tags: { page: 'public' } }],
  ])
  check(page, { 'page 200': (r) => r.status === 200 })
  check(booking, { 'options 200': (r) => r.status === 200 })
  sleep(1 + Math.random() * 2)

  const services = booking.status === 200 ? booking.json('services') : []
  const service = services && services.length ? services[0].id : null
  const free = http.get(`${BASE}/api/public/${slug}/availability${service ? `?service_ids[]=${service}` : ''}`, { headers, tags: { page: 'availability' } })
  check(free, { 'availability 200': (r) => r.status === 200 })
  sleep(1 + Math.random() * 3)

  if (Math.random() < 0.1 && free.status === 200 && service) {
    const day = (free.json('days') || []).find((d) => d.times.length)
    if (day) {
      const time = day.times[Math.floor(Math.random() * day.times.length)]
      const res = http.post(
        `${BASE}/api/public/${slug}/booking-requests`,
        JSON.stringify({
          client_name: 'Cliente Carga',
          client_whatsapp: '11912345678',
          client_email: `carga${__VU}.${__ITER}@example.com`,
          service_ids: [service],
          date: day.date,
          time,
        }),
        { headers: { ...headers, 'Content-Type': 'application/json' }, tags: { page: 'booking' } },
      )
      check(res, { 'booking 201 or 409': (r) => r.status === 201 || r.status === 409 })
    }
  }
  sleep(1)
}
