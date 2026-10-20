import { ref } from 'vue'
import type { Point } from '@/utils/address'

export const GeolocationStatus = {
  Idle: 'idle',
  Asking: 'asking',
  Granted: 'granted',
  /** The person said no (or the browser blocks it). */
  Denied: 'denied',
  /** No GPS/location on this device, or it took too long. */
  Unavailable: 'unavailable',
} as const

export type GeolocationStatus = (typeof GeolocationStatus)[keyof typeof GeolocationStatus]

const TIMEOUT_MS = 10_000
const MAX_AGE_MS = 5 * 60_000

/** The device location, only when the person asks for it (the browser shows its own permission prompt). */
export function useGeolocation() {
  const status = ref<GeolocationStatus>(GeolocationStatus.Idle)
  const point = ref<Point | null>(null)

  function request(): Promise<Point | null> {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
      status.value = GeolocationStatus.Unavailable
      return Promise.resolve(null)
    }
    status.value = GeolocationStatus.Asking
    return new Promise((resolve) => {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          point.value = { latitude: position.coords.latitude, longitude: position.coords.longitude }
          status.value = GeolocationStatus.Granted
          resolve(point.value)
        },
        (error) => {
          point.value = null
          status.value = error.code === error.PERMISSION_DENIED ? GeolocationStatus.Denied : GeolocationStatus.Unavailable
          resolve(null)
        },
        { enableHighAccuracy: false, timeout: TIMEOUT_MS, maximumAge: MAX_AGE_MS },
      )
    })
  }

  return { status, point, request }
}
