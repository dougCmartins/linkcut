/*
 * linkcut service worker: shows the push notifications sent by the API (e.g. a new booking request)
 * even with the site closed, and opens the page of the notification when it is tapped.
 * Payload: { title, body, url, tag } (Domain/Notification/Data/PushMessageData on the API).
 */
self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()))

self.addEventListener('push', (event) => {
  let message = { title: 'linkcut', body: '', url: '/painel', tag: 'linkcut' }
  try {
    message = { ...message, ...event.data.json() }
  } catch {
    // Not JSON: show the generic title.
  }
  event.waitUntil(
    self.registration.showNotification(message.title, {
      body: message.body,
      tag: message.tag,
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-192.png',
      data: { url: message.url },
    }),
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = new URL(event.notification.data?.url ?? '/painel', self.location.origin).href
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      const open = windows.find((client) => client.url.startsWith(self.location.origin))
      if (open) return open.navigate(url).then((client) => (client ?? open).focus())
      return self.clients.openWindow(url)
    }),
  )
})
