import http from 'k6/http'
import { check } from 'k6'

// How many page opens per second the server takes: each "open" is the profile + the booking options, at a rising
// rate, until requests start to slow down or fail.
const BASE = __ENV.BASE || 'http://127.0.0.1:8081'

export const options = {
  scenarios: {
    opens: {
      executor: 'ramping-arrival-rate',
      startRate: 20,
      timeUnit: '1s',
      preAllocatedVUs: 200,
      maxVUs: 600,
      stages: [
        { duration: '20s', target: 50 },
        { duration: '20s', target: 100 },
        { duration: '20s', target: 200 },
        { duration: '20s', target: 300 },
      ],
    },
  },
  thresholds: { http_req_failed: ['rate<0.01'], http_req_duration: ['p(95)<1000'] },
}

export default function () {
  const headers = { 'X-Forwarded-For': `10.${(__VU >> 8) & 255}.${__VU & 255}.${__ITER % 250}`, Accept: 'application/json' }
  const slug = `perf-${Math.floor(Math.random() * 200)}`
  const responses = http.batch([
    ['GET', `${BASE}/api/public/${slug}`, null, { headers }],
    ['GET', `${BASE}/api/public/${slug}/booking-options`, null, { headers }],
  ])
  check(responses[0], { '200': (r) => r.status === 200 })
}
