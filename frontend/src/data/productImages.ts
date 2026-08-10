/** Self-hosted product photos (served from frontend/public/products). */
export const PRODUCT_IMAGE_POOL = [
  '/products/magnetic-bookmarks.jpeg',
  '/products/classic-bookmarks.jpeg',
  '/products/birthday-cards.jpeg',
  '/products/thank-you-cards.jpeg',
  '/products/love-cards.jpeg',
  '/products/sorry-cards.jpeg',
  '/products/hampers.jpeg',
  '/products/mini-hamper.jpeg',
  '/products/fridge-magnets.jpeg',
  '/products/couple-fridge-magnets.jpeg',
  '/products/calendars.jpeg',
  '/products/new-calendars.jpeg',
] as const

export const DEFAULT_PRODUCT_IMAGE = PRODUCT_IMAGE_POOL[0]

/** Legacy WordPress wp-content URLs → local /products paths. */
export const LEGACY_WP_IMAGE_MAP: Record<string, string> = {
  'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg':
    '/products/magnetic-bookmarks.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Whimsical-Bookmark-Collection_-700x700.jpeg':
    '/products/classic-bookmarks.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Panda-Pun-Birthday-Card-Have-a-panda-stic-Birthday-Panda-Birthday-Card-Birthday-Card-for-Friend-Birthday-Card-for-Nephew-Niece-700x700.jpeg':
    '/products/birthday-cards.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Set-of-3-thank-you-cards-two-toned-theme-thank-you-card-pack-handmade-thank-you-cards-card-assortment-thank-you-card-variety-pack-700x700.jpeg':
    '/products/thank-you-cards.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Youre-My-Favourite-Person-Card-_-Valentines-Card-_-Be-My-Valentine-_-Love-You-Card-_-Valentine-Card-_-Watercolour-Hearts-Card-_-With-Love-700x700.jpeg':
    '/products/love-cards.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Im-Sorry-Card-Printable_-Rewind-Cassette-Tape-Design-digital-Download-Etsy-700x700.jpeg':
    '/products/sorry-cards.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Visit-Now_-Customize-a-Birthday-Hamper-for-Your-Bestie-700x700.jpeg':
    '/products/hampers.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/gift-hamper-for-her-700x700.jpeg':
    '/products/mini-hamper.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/download-43-700x700.jpeg':
    '/products/fridge-magnets.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/Personalisierte-Save-the-Date-Kuhlschrankmagnet-Kalender-Hochzeit-Einladung-Ankundigung-Geschenk-700x700.jpeg':
    '/products/couple-fridge-magnets.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/download-44-700x700.jpeg':
    '/products/calendars.jpeg',
  'https://theminimark.com/wp-content/uploads/2026/03/download-45-700x700.jpeg':
    '/products/new-calendars.jpeg',
}

function hashString(input: string): number {
  let hash = 0
  for (let i = 0; i < input.length; i += 1) {
    hash = (hash * 31 + input.charCodeAt(i)) | 0
  }
  return Math.abs(hash)
}

export const IMAGES_BY_CATEGORY = {
  bookmarks: ['/products/magnetic-bookmarks.jpeg', '/products/classic-bookmarks.jpeg'],
  cards: [
    '/products/birthday-cards.jpeg',
    '/products/thank-you-cards.jpeg',
    '/products/love-cards.jpeg',
    '/products/sorry-cards.jpeg',
  ],
  calendars: ['/products/calendars.jpeg', '/products/new-calendars.jpeg'],
  magnets: ['/products/fridge-magnets.jpeg', '/products/couple-fridge-magnets.jpeg'],
  hampers: ['/products/hampers.jpeg', '/products/mini-hamper.jpeg'],
} as const

export function pickProductImageFromPool(key: string, category?: string): string {
  if (category && category in IMAGES_BY_CATEGORY) {
    const pool = IMAGES_BY_CATEGORY[category as keyof typeof IMAGES_BY_CATEGORY]
    return pool[hashString(key) % pool.length]
  }
  const pool = PRODUCT_IMAGE_POOL as readonly string[]
  return pool[hashString(key) % pool.length]
}

export function isBrokenPlaceholderImage(url: string): boolean {
  return /picsum\.photos/i.test(url)
}

export function rewriteLegacyProductImageUrl(url: string): string {
  const trimmed = url.trim()
  if (!trimmed) return trimmed
  if (LEGACY_WP_IMAGE_MAP[trimmed]) return LEGACY_WP_IMAGE_MAP[trimmed]
  if (/wp-content\/uploads/i.test(trimmed)) {
    return pickProductImageFromPool(trimmed)
  }
  return trimmed
}
