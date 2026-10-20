import { afterEach, describe, expect, it, vi } from 'vitest'
import { GeolocationStatus, useGeolocation } from './useGeolocation'

function deviceAnswers(answer: { latitude: number; longitude: number } | { errorCode: number }): void {
  const getCurrentPosition = vi.fn((success: PositionCallback, failure: PositionErrorCallback) => {
    if ('errorCode' in answer) failure({ code: answer.errorCode, PERMISSION_DENIED: 1 } as GeolocationPositionError)
    else success({ coords: answer } as GeolocationPosition)
  })
  Object.defineProperty(navigator, 'geolocation', { value: { getCurrentPosition }, configurable: true })
}

describe('useGeolocation', () => {
  afterEach(() => {
    Object.defineProperty(navigator, 'geolocation', { value: undefined, configurable: true })
  })

  it('gives the point when the person allows it', async () => {
    // Arrange
    deviceAnswers({ latitude: -23.5, longitude: -46.6 })
    const geolocation = useGeolocation()

    // Act
    const point = await geolocation.request()

    // Assert
    expect(point).toEqual({ latitude: -23.5, longitude: -46.6 })
    expect(geolocation.status.value).toBe(GeolocationStatus.Granted)
  })

  it('tells a refusal apart from a device without location', async () => {
    // Arrange
    deviceAnswers({ errorCode: 1 })
    const denied = useGeolocation()
    const timedOut = useGeolocation()

    // Act
    await denied.request()
    deviceAnswers({ errorCode: 3 })
    await timedOut.request()

    // Assert
    expect(denied.status.value).toBe(GeolocationStatus.Denied)
    expect(timedOut.status.value).toBe(GeolocationStatus.Unavailable)
    expect(denied.point.value).toBeNull()
  })

  it('is unavailable when the browser has no geolocation', async () => {
    // Arrange
    const geolocation = useGeolocation()

    // Act
    const point = await geolocation.request()

    // Assert
    expect(point).toBeNull()
    expect(geolocation.status.value).toBe(GeolocationStatus.Unavailable)
  })
})
