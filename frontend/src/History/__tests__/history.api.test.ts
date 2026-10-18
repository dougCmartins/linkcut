import { describe, expect, it } from 'vitest'
import { fetchCompletedServices, fetchMonthToDateFinances } from '../history.api'
import { buildHistoryCsv, historyFileName } from '../historyExport'

const NOW = new Date(2026, 9, 17, 15, 0) // Saturday 17 Oct 2026, 15:00

describe('fetchCompletedServices (mock)', () => {
  it('returns only the records inside the range, newest first', async () => {
    // Arrange
    const range = { from: '2026-10-01', to: '2026-10-17' }

    // Act
    const records = await fetchCompletedServices(range, NOW)

    // Assert
    expect(records.length).toBeGreaterThan(0)
    expect(records.every((record) => record.date >= range.from && record.date <= range.to)).toBe(true)
    const keys = records.map((record) => `${record.date} ${record.time}`)
    expect(keys).toEqual([...keys].sort().reverse())
  })

  it('never lists Sundays (closed) nor times later than now', async () => {
    // Arrange
    const range = { from: '2026-09-01', to: '2026-10-17' }

    // Act
    const records = await fetchCompletedServices(range, NOW)

    // Assert
    expect(records.some((record) => new Date(`${record.date}T12:00`).getDay() === 0)).toBe(false)
    expect(records.filter((record) => record.date === '2026-10-17').every((record) => record.time <= '15:00')).toBe(true)
  })

  it('gives the same records for the same day on every call', async () => {
    // Arrange
    const range = { from: '2026-10-05', to: '2026-10-05' }

    // Act
    const [first, second] = await Promise.all([
      fetchCompletedServices(range, NOW),
      fetchCompletedServices(range, new Date(2026, 9, 18, 9, 0)),
    ])

    // Assert
    expect(second).toEqual(first)
  })
})

describe('fetchMonthToDateFinances', () => {
  it('matches the history total of the month until today', async () => {
    // Arrange
    const monthRecords = await fetchCompletedServices({ from: '2026-10-01', to: '2026-10-17' }, NOW)

    // Act
    const finances = await fetchMonthToDateFinances(NOW)

    // Assert
    expect(finances.revenueInCents).toBe(monthRecords.reduce((sum, record) => sum + record.priceInCents, 0))
    expect(finances.expensesInCents).toBeGreaterThan(0)
  })
})

describe('buildHistoryCsv', () => {
  it('writes Brazilian dates and decimal comma values', () => {
    // Arrange
    const records = [
      { id: '1', date: '2026-10-17', time: '14:30', clientName: 'João Silva', serviceName: 'Corte', priceInCents: 6550 },
    ]
    const headers = { date: 'Data', time: 'Hora', client: 'Cliente', service: 'Serviço', value: 'Valor (R$)' }

    // Act
    const csv = buildHistoryCsv(records, headers)

    // Assert
    expect(csv).toContain('Data;Hora;Cliente;Serviço;Valor (R$)\r\n17/10/2026;14:30;João Silva;Corte;65,50\r\n')
    expect(historyFileName({ from: '2026-10-01', to: '2026-10-17' })).toBe('historico-linkcut_2026-10-01_a_2026-10-17.csv')
  })
})
