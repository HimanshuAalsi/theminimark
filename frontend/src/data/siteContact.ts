/** Public contact & social links */

export const SITE_EMAIL = 'support@theminimark.com'

export const SITE_PHONE = '9625805071'
export const SITE_PHONE_DISPLAY = '+91 96258 05071'

export const SITE_INSTAGRAM_HANDLE = '@theminimark'
export const SITE_INSTAGRAM_URL =
  'https://www.instagram.com/theminimark?igsh=MTc2OGhpYTJsMHhicA=='

/** E.164 without + — used for wa.me links */
export const SITE_WHATSAPP_PHONE = '919625805071'

export const SITE_WHATSAPP_CHANNEL_URL =
  'https://whatsapp.com/channel/0029VbCpSP76mYPTzD857o3t'

export const SITE_SOCIAL = [
  {
    id: 'instagram',
    label: 'Instagram',
    handle: SITE_INSTAGRAM_HANDLE,
    href: SITE_INSTAGRAM_URL,
  },
  {
    id: 'facebook',
    label: 'Facebook',
    handle: '@theminimark36',
    href: 'https://www.facebook.com/theminimark36',
  },
  {
    id: 'youtube',
    label: 'YouTube',
    handle: '@theminimark',
    href: 'https://youtube.com/@theminimark?si=nPZqL4YBKbghx6tp',
  },
] as const

export function whatsappOrderLink(message: string): string {
  const text = encodeURIComponent(message)
  return `https://wa.me/${SITE_WHATSAPP_PHONE}?text=${text}`
}

export function mailto(href = SITE_EMAIL): string {
  return `mailto:${href}`
}
