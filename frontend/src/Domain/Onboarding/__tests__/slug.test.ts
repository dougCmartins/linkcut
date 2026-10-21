import { describe, expect, it } from 'vitest'
import { RESERVED_SLUGS, isReservedSlug, isValidSlug, slugify } from '../slug'
import { routes } from '@/router'

describe('slug', () => {
  it('turns a barbershop name into a link', () => {
    // Arrange & Act & Assert
    expect(slugify('Barbearia do João!')).toBe('barbearia-do-joao')
    expect(slugify('  Navalha  &  Cia  ')).toBe('navalha-cia')
  })

  it('accepts lowercase letters, numbers and hyphens, from 3 to 40 characters', () => {
    // Arrange & Act & Assert
    expect(isValidSlug('rafael-barber')).toBe(true)
    expect(isValidSlug('ab')).toBe(false)
    expect(isValidSlug('Rafael')).toBe(false)
    expect(isValidSlug('fim-')).toBe(false)
  })

  it('reserves the first segment of every fixed page of the app', () => {
    // Arrange
    const fixedSegments = routes.map((route) => route.path.split('/')[1] ?? '').filter((segment) => segment && !segment.startsWith(':'))

    // Act & Assert
    for (const segment of new Set(fixedSegments)) expect(RESERVED_SLUGS).toContain(segment)
    expect(isReservedSlug('painel')).toBe(true)
    expect(isReservedSlug('rafael')).toBe(false)
  })
})
