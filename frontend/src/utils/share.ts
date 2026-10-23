/** Share targets of a public page. Each opens the app or site with the text and link ready. */
export const ShareTarget = {
  WhatsApp: 'whatsapp',
  Facebook: 'facebook',
  X: 'x',
  Telegram: 'telegram',
} as const
export type ShareTarget = (typeof ShareTarget)[keyof typeof ShareTarget]

export function shareUrl(target: ShareTarget, url: string, text: string): string {
  const link = encodeURIComponent(url)
  const message = encodeURIComponent(text)
  switch (target) {
    case ShareTarget.WhatsApp:
      return `https://wa.me/?text=${encodeURIComponent(`${text} ${url}`)}`
    case ShareTarget.Facebook:
      return `https://www.facebook.com/sharer/sharer.php?u=${link}`
    case ShareTarget.X:
      return `https://x.com/intent/post?text=${message}&url=${link}`
    case ShareTarget.Telegram:
      return `https://t.me/share/url?url=${link}&text=${message}`
  }
}

/** "https://linkcut.app/rafael" → "linkcut.app/rafael" (shown on the card). */
export function displayUrl(url: string): string {
  return url.replace(/^https?:\/\//, '').replace(/\/$/, '')
}
