import type { CompletedService } from '@/History/history.model'
import { toIsoDate } from '@/utils/date'
import { fromMinutes, toMinutes } from '@/utils/time'

/**
 * Mock history of finished appointments until the backend exists.
 * Every day is generated from a seed made of its date, so the same day always
 * shows the same appointments (numbers do not change between reloads).
 */

const HISTORY_DAYS = 730
const SUNDAY = 0
const SATURDAY = 6
const OPENING = '09:00'
const SLOT_MINUTES = 45

const SERVICES = [
  { name: 'Corte', priceInCents: 5000, weight: 45 },
  { name: 'Barba', priceInCents: 4000, weight: 20 },
  { name: 'Corte + Barba', priceInCents: 8000, weight: 25 },
  { name: 'Pigmentação', priceInCents: 6550, weight: 10 },
]

const CLIENTS = [
  'Lucas Andrade', 'Bruno Lima', 'Pedro Martins', 'Gabriel Souza', 'Rafael Nunes', 'Mateus Rocha', 'Thiago Alves',
  'Felipe Barros', 'Diego Ramos', 'André Costa', 'João Pereira', 'Caio Mendes', 'Vinícius Lopes', 'Gustavo Reis',
  'Leonardo Dias', 'Rodrigo Pinto', 'Eduardo Freitas', 'Marcelo Teixeira', 'Henrique Castro', 'Daniel Moreira',
  'Igor Carvalho', 'Samuel Araújo', 'Renan Gomes', 'Otávio Melo', 'Murilo Cardoso', 'Arthur Ribeiro',
]

/** Fixed costs of the month (mock): chair rent on day 5, products every Monday. */
const RENT = { day: 5, amountInCents: 150_000 }
const WEEKLY_PRODUCTS_IN_CENTS = 18_000
const MONDAY = 1

function hashString(text: string): number {
  let hash = 2166136261
  for (let index = 0; index < text.length; index++) {
    hash ^= text.charCodeAt(index)
    hash = Math.imul(hash, 16777619)
  }
  return hash >>> 0
}

/** Small deterministic random generator (mulberry32). */
function seededRandom(seed: number): () => number {
  let state = seed
  return () => {
    state = (state + 0x6d2b79f5) | 0
    let value = Math.imul(state ^ (state >>> 15), 1 | state)
    value = (value + Math.imul(value ^ (value >>> 7), 61 | value)) ^ value
    return ((value ^ (value >>> 14)) >>> 0) / 4294967296
  }
}

function pickService(random: () => number) {
  const totalWeight = SERVICES.reduce((sum, service) => sum + service.weight, 0)
  let target = random() * totalWeight
  for (const service of SERVICES) {
    target -= service.weight
    if (target < 0) return service
  }
  return SERVICES[0]!
}

function servicesOn(date: Date, now: Date): CompletedService[] {
  if (date.getDay() === SUNDAY) return []
  const isoDate = toIsoDate(date)
  const random = seededRandom(hashString(isoDate))
  const isSaturday = date.getDay() === SATURDAY
  const count = isSaturday ? 3 + Math.floor(random() * 4) : 4 + Math.floor(random() * 5)
  const slotCount = isSaturday ? 7 : 13
  const slots = new Set<number>()
  while (slots.size < count) slots.add(Math.floor(random() * slotCount))

  const isToday = isoDate === toIsoDate(now)
  const nowMinutes = now.getHours() * 60 + now.getMinutes()
  return [...slots]
    .sort((a, b) => a - b)
    .map((slot, index) => {
      const service = pickService(random)
      const client = CLIENTS[Math.floor(random() * CLIENTS.length)]!
      return {
        id: `${isoDate}-${index}`,
        date: isoDate,
        time: fromMinutes(toMinutes(OPENING) + slot * SLOT_MINUTES),
        clientName: client,
        serviceName: service.name,
        priceInCents: service.priceInCents,
      }
    })
    .filter((record) => !isToday || toMinutes(record.time) <= nowMinutes)
}

let cache: { key: string; records: CompletedService[] } | null = null

function generatedServices(now: Date): CompletedService[] {
  const key = `${toIsoDate(now)} ${now.getHours()}:${now.getMinutes()}`
  if (cache?.key === key) return cache.records
  const records: CompletedService[] = []
  for (let offset = 0; offset < HISTORY_DAYS; offset++) {
    const day = new Date(now.getFullYear(), now.getMonth(), now.getDate() - offset)
    records.push(...servicesOn(day, now).reverse())
  }
  cache = { key, records }
  return records
}

/* Appointments the barber concluded in the app ("Concluir atendimento"), kept in the browser. */
const RECORDED_KEY = 'linkcut.mockHistory.completed'

function readRecorded(): CompletedService[] {
  try {
    const raw = localStorage.getItem(RECORDED_KEY)
    return raw ? (JSON.parse(raw) as CompletedService[]) : []
  } catch {
    return []
  }
}

function writeRecorded(records: CompletedService[]): void {
  try {
    localStorage.setItem(RECORDED_KEY, JSON.stringify(records))
  } catch {
    // Storage blocked: the record lasts while the page is open.
  }
}

/** Saves a concluded appointment. The id is the booking request id, so it never counts twice. */
export function mockRecordCompletedService(record: CompletedService): void {
  writeRecorded([...readRecorded().filter((item) => item.id !== record.id), record])
}

export function mockRemoveCompletedService(id: string): void {
  writeRecorded(readRecorded().filter((item) => item.id !== id))
}

const newestFirst = (a: CompletedService, b: CompletedService) =>
  `${b.date} ${b.time}`.localeCompare(`${a.date} ${a.time}`)

/** All finished appointments from ~2 years ago until `now` (generated + concluded in the app), newest first. */
export function mockCompletedServices(now: Date): CompletedService[] {
  const recorded = readRecorded()
  const generated = generatedServices(now)
  return recorded.length ? [...recorded, ...generated].sort(newestFirst) : generated
}

/** Costs of the current month until `now`. */
export function mockMonthToDateExpenses(now: Date): number {
  let total = now.getDate() >= RENT.day ? RENT.amountInCents : 0
  for (let day = 1; day <= now.getDate(); day++) {
    if (new Date(now.getFullYear(), now.getMonth(), day).getDay() === MONDAY) total += WEEKLY_PRODUCTS_IN_CENTS
  }
  return total
}
