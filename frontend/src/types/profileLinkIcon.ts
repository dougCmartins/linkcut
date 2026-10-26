export const ProfileLinkIcon = {
  Link: 'link',
  Phone: 'phone',
  Map: 'map',
} as const

export type ProfileLinkIcon = (typeof ProfileLinkIcon)[keyof typeof ProfileLinkIcon]

export const PROFILE_LINK_ICONS: ProfileLinkIcon[] = Object.values(ProfileLinkIcon)
