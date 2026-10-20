import { http } from '@/http/client'

/** Push notifications API: the server key and the devices of the signed-in barber. */

export async function fetchPushPublicKey(): Promise<string> {
  const { data } = await http<{ public_key: string }>('/notifications/push/public-key')
  return data.public_key
}

/** `subscription` is PushSubscription.toJSON(): { endpoint, keys: { p256dh, auth } }. */
export async function savePushSubscription(subscription: PushSubscriptionJSON): Promise<void> {
  await http<void>('/notifications/push/subscriptions', {
    method: 'POST',
    body: { endpoint: subscription.endpoint, keys: subscription.keys },
  })
}

export async function deletePushSubscription(endpoint: string): Promise<void> {
  await http<void>('/notifications/push/subscriptions', { method: 'DELETE', body: { endpoint } })
}
