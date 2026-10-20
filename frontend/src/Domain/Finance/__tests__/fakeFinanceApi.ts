import { envelope, installFakeApi, type FakeCall, type FakeHandler } from '@/utils/testFetch'

/**
 * A fake Finance API with the same contract as the backend (snake_case, soft delete + restore,
 * month-to-date with the same rules). "Today" is fixed so the numbers are predictable.
 */

export interface RevenueRow {
  id: number
  date: string
  time: string
  client_name: string
  service_name: string
  price_in_cents: number
  cost_in_cents: number
}

interface FixedRow {
  id: number
  name: string
  amount_in_cents: number
  due_day: number
  deleted?: boolean
}

interface OneOffRow {
  id: number
  description: string
  amount_in_cents: number
  date: string
  deleted?: boolean
}

export const TODAY = '2026-10-17'

const isoDate = (date: Date) =>
  `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`

/** Three appointments every day from Jan 1st until `today` (Corte, Barba, Corte + Barba). */
export function createHistory(today = TODAY): RevenueRow[] {
  const rows: RevenueRow[] = []
  const services: [string, number, number][] = [
    ['Corte', 5000, 600],
    ['Barba', 4000, 400],
    ['Corte + Barba', 8000, 1000],
  ]
  for (let day = new Date(`${today.slice(0, 4)}-01-01T12:00`); isoDate(day) <= today; day.setDate(day.getDate() + 1)) {
    services.forEach(([service, price, cost], index) =>
      rows.push({
        id: rows.length + 1,
        date: isoDate(day),
        time: `${String(9 + index * 2).padStart(2, '0')}:00`,
        client_name: `Cliente ${rows.length + 1}`,
        service_name: service,
        price_in_cents: price,
        cost_in_cents: cost,
      }),
    )
  }
  return rows
}

export function createFixedExpenses(): FixedRow[] {
  return [
    { id: 1, name: 'Aluguel da cadeira', amount_in_cents: 150_000, due_day: 5 },
    { id: 2, name: 'Internet', amount_in_cents: 12_000, due_day: 10 },
    { id: 3, name: 'DAS (MEI)', amount_in_cents: 7_590, due_day: 20 },
  ]
}

export function createOneOffExpenses(): OneOffRow[] {
  return [
    { id: 1, description: 'Lâminas e pomadas', amount_in_cents: 21_000, date: '2026-10-02' },
    { id: 2, description: 'Toalhas descartáveis', amount_in_cents: 8_500, date: '2026-10-12' },
  ]
}

const newestFirst = (a: RevenueRow, b: RevenueRow) => `${b.date} ${b.time}`.localeCompare(`${a.date} ${a.time}`) || b.id - a.id
const sum = <T>(rows: T[], pick: (row: T) => number) => rows.reduce((total, row) => total + pick(row), 0)

type FinanceOptions = { today?: string; history?: RevenueRow[]; fixed?: FixedRow[]; oneOff?: OneOffRow[] }

export function installFakeFinanceApi(options: FinanceOptions = {}, extraRoutes: Record<string, FakeHandler> = {}) {
  const finance = createFinanceRoutes(options)
  return { ...installFakeApi({ ...finance.routes, ...extraRoutes }), state: finance.state, monthToDate: finance.monthToDate }
}

/** The routes alone, to combine with another fake API (e.g. the dashboard also needs Settings). */
export function createFinanceRoutes(options: FinanceOptions = {}) {
  const today = options.today ?? TODAY
  const state = {
    history: options.history ?? createHistory(today),
    fixed: options.fixed ?? createFixedExpenses(),
    oneOff: options.oneOff ?? createOneOffExpenses(),
  }
  let nextId = 100
  const monthStart = `${today.slice(0, 8)}01`
  const lastDayOfMonth = new Date(Number(today.slice(0, 4)), Number(today.slice(5, 7)), 0).getDate()

  const inPeriod = (from: string, to: string) =>
    state.history.filter((row) => row.date >= from && row.date <= to).sort(newestFirst)
  const live = <T extends { deleted?: boolean }>(rows: T[]) => rows.filter((row) => !row.deleted)
  const strip = <T extends { deleted?: boolean }>({ deleted: _deleted, ...row }: T) => row
  const find = <T extends { id: number }>(rows: T[], call: FakeCall) => rows.find((row) => row.id === Number(call.params.id))

  function monthToDate() {
    const revenue = inPeriod(monthStart, today)
    const fixed = sum(
      live(state.fixed).filter((row) => Math.min(row.due_day, lastDayOfMonth) <= Number(today.slice(8))),
      (row) => row.amount_in_cents,
    )
    const oneOff = sum(
      live(state.oneOff).filter((row) => row.date >= monthStart && row.date <= today),
      (row) => row.amount_in_cents,
    )
    const serviceCosts = sum(revenue, (row) => row.cost_in_cents)
    return {
      from: monthStart,
      to: today,
      revenue_in_cents: sum(revenue, (row) => row.price_in_cents),
      expenses: {
        fixed_in_cents: fixed,
        one_off_in_cents: oneOff,
        service_costs_in_cents: serviceCosts,
        total_in_cents: fixed + oneOff + serviceCosts,
      },
    }
  }

  /** PUT / DELETE / restore for one kind of expense. */
  function expenseRoutes<T extends { id: number; deleted?: boolean }>(path: string, rows: () => T[], add: (row: T) => void) {
    return {
      [`POST /finance/${path}`]: ({ body }: FakeCall) => {
        const row = { ...(body as object), id: nextId++ } as T
        add(row)
        return { status: 201, body: row }
      },
      [`PUT /finance/${path}/:id`]: (call: FakeCall) => {
        const row = find(live(rows()), call)
        if (!row) return envelope('EXPENSE_NOT_FOUND', 404)
        Object.assign(row, call.body)
        return { status: 200, body: strip(row) }
      },
      [`DELETE /finance/${path}/:id`]: (call: FakeCall) => {
        const row = find(live(rows()), call)
        if (!row) return envelope('EXPENSE_NOT_FOUND', 404)
        row.deleted = true
        return { status: 204 }
      },
      [`POST /finance/${path}/:id/restore`]: (call: FakeCall) => {
        const row = find(rows(), call)
        if (!row) return envelope('EXPENSE_NOT_FOUND', 404)
        row.deleted = false
        return { status: 201, body: strip(row) }
      },
    }
  }

  const routes: Record<string, FakeHandler> = {
    'GET /finance/history': ({ query }: FakeCall) => {
      const rows = inPeriod(query.get('from') ?? '', query.get('to') ?? '')
      const page = Number(query.get('page') ?? 1)
      const perPage = Number(query.get('per_page') ?? 50)
      const total = sum(rows, (row) => row.price_in_cents)
      return {
        status: 200,
        body: {
          items: rows.slice((page - 1) * perPage, page * perPage),
          summary: { total_in_cents: total, count: rows.length, average_ticket_in_cents: rows.length ? Math.round(total / rows.length) : 0 },
          page,
          per_page: perPage,
          last_page: Math.max(1, Math.ceil(rows.length / perPage)),
        },
      }
    },
    'GET /finance/history/export': ({ query }: FakeCall) => {
      const rows = inPeriod(query.get('from') ?? '', query.get('to') ?? '')
      const lines = ['Data;Hora;Cliente;Serviço;"Valor (R$)"', ...rows.map((row) => `${row.date};${row.time};${row.client_name}`)]
      return { status: 200, text: `﻿${lines.join('\r\n')}\r\n` }
    },
    'GET /finance/month-to-date': () => ({ status: 200, body: monthToDate() }),
    'GET /finance/expenses': () => ({
      status: 200,
      body: {
        fixed: live(state.fixed).map(strip),
        one_off: live(state.oneOff).filter((row) => row.date >= monthStart).map(strip),
      },
    }),
    ...expenseRoutes('fixed-expenses', () => state.fixed, (row) => state.fixed.push(row)),
    ...expenseRoutes('one-off-expenses', () => state.oneOff, (row) => state.oneOff.push(row)),
  }

  return { routes, state, monthToDate }
}
