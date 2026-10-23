import { describe, expect, it } from 'vitest'
import { ShareTarget, displayUrl, shareUrl } from './share'

const URL_ = 'https://linkcut.app/rafael'
const TEXT = 'Agende seu horário na Rafael & Cia:'

describe('share', () => {
  it('puts the text and the link in the WhatsApp message', () => {
    // Arrange / Act
    const url = new URL(shareUrl(ShareTarget.WhatsApp, URL_, TEXT))

    // Assert
    expect(url.host).toBe('wa.me')
    expect(url.searchParams.get('text')).toBe(`${TEXT} ${URL_}`)
  })

  it('encodes the link for Facebook, X and Telegram', () => {
    // Arrange / Act
    const facebook = new URL(shareUrl(ShareTarget.Facebook, URL_, TEXT))
    const x = new URL(shareUrl(ShareTarget.X, URL_, TEXT))
    const telegram = new URL(shareUrl(ShareTarget.Telegram, URL_, TEXT))

    // Assert
    expect(facebook.searchParams.get('u')).toBe(URL_)
    expect([x.searchParams.get('url'), x.searchParams.get('text')]).toEqual([URL_, TEXT])
    expect([telegram.searchParams.get('url'), telegram.searchParams.get('text')]).toEqual([URL_, TEXT])
  })

  it('shows the link without the protocol', () => {
    expect(displayUrl('https://linkcut.app/rafael/')).toBe('linkcut.app/rafael')
  })
})
