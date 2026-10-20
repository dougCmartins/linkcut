import { describe, expect, it } from 'vitest'
import { distanceInMeters, formatAddress, formatDistance, formatZipCode, mapsUrl } from './address'
import { emptyAddress } from '@/Domain/Settings/settings.model'

const address = {
  ...emptyAddress(),
  zipCode: '01304001',
  street: 'Rua Augusta',
  number: '1500',
  complement: 'sala 2',
  district: 'Consolação',
  city: 'São Paulo',
  state: 'SP',
}

describe('address utils', () => {
  it('formats the CEP while it is typed', () => {
    // Arrange & Act
    const formatted = ['013', '01304', '013040', '01304-001', '013040019'].map(formatZipCode)

    // Assert
    expect(formatted).toEqual(['013', '01304', '01304-0', '01304-001', '01304-001'])
  })

  it('writes the address in one line, skipping an empty complement', () => {
    // Arrange & Act
    const full = formatAddress(address)
    const withoutComplement = formatAddress({ ...address, complement: '' })

    // Assert
    expect(full).toBe('Rua Augusta, 1500, sala 2 – Consolação, São Paulo/SP')
    expect(withoutComplement).toBe('Rua Augusta, 1500 – Consolação, São Paulo/SP')
  })

  it('links to the address on Google Maps', () => {
    // Arrange & Act
    const url = mapsUrl(address)

    // Assert
    expect(url.startsWith('https://www.google.com/maps/search/?api=1&query=')).toBe(true)
    expect(decodeURIComponent(url)).toContain('Rua Augusta, 1500, sala 2 – Consolação, São Paulo/SP, 01304-001')
  })

  it('measures the straight line like the API', () => {
    // Arrange & Act: Praça da Sé (São Paulo) → Centro (Rio de Janeiro)
    const meters = distanceInMeters({ latitude: -23.5505, longitude: -46.6333 }, { latitude: -22.9068, longitude: -43.1729 })

    // Assert
    expect(meters).toBe(360_749)
  })

  it('shows meters up close and kilometers further away', () => {
    // Arrange & Act
    const labels = [850, 3240, 12_000, 125_400].map(formatDistance)

    // Assert
    expect(labels).toEqual(['850 m', '3,2 km', '12 km', '125 km'])
  })
})
