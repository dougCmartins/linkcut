import { beforeEach, describe, expect, it } from 'vitest'
import { keepGoogleNonce, newGoogleNonce, takeGoogleNonce } from '../googleNonce'

describe('googleNonce', () => {
  beforeEach(() => localStorage.clear())

  it('creates a different random nonce each time', () => {
    // Arrange / Act
    const first = newGoogleNonce()
    const second = newGoogleNonce()

    // Assert
    expect(first).toMatch(/^[0-9a-f]{48}$/)
    expect(second).not.toBe(first)
  })

  it('gives the kept nonce back only once', () => {
    // Arrange
    keepGoogleNonce('nonce-1')

    // Act
    const taken = takeGoogleNonce()
    const again = takeGoogleNonce()

    // Assert
    expect(taken).toBe('nonce-1')
    expect(again).toBe('')
  })
})
