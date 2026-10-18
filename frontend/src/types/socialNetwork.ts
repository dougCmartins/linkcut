export const SocialNetwork = {
  Instagram: 'instagram',
  TikTok: 'tiktok',
  YouTube: 'youtube',
} as const

export type SocialNetwork = (typeof SocialNetwork)[keyof typeof SocialNetwork]
