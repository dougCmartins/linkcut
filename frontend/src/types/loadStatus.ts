/** State of data loaded from the API. */
export const LoadStatus = {
  Idle: 'idle',
  Loading: 'loading',
  Ready: 'ready',
  NotFound: 'notFound',
  Failed: 'failed',
} as const

export type LoadStatus = (typeof LoadStatus)[keyof typeof LoadStatus]
