import type { ShopCategory, SiteProduct } from '@/data/siteContent'

export interface ProductDetail extends SiteProduct {
  description: string | null
  features: string[]
  images: string[]
}

export const CATEGORY_FEATURES: Record<ShopCategory, string[]> = {
  bookmarks: [
    'Fold-over magnetic clip stays put',
    'Glossy, crease-resistant finish',
    'Ultra slim — barely adds bulk',
    'Perfect for novels & textbooks',
  ],
  cards: [
    'Rich paper stock with envelope-ready size',
    'Blank inside for your message',
    'Vibrant print on the front',
    'Ideal for birthdays & thank-yous',
  ],
  calendars: [
    'Clear month-at-a-glance layout',
    'Desk or wall friendly sizing',
    'Quality paper that lays flat',
    'A calm companion for your desk',
  ],
  magnets: [
    'Strong hold on fridge or locker',
    'Glossy photo-ready surface',
    'Lightweight & gift-friendly',
    'Pairs well with bookmarks & cards',
  ],
  hampers: [
    'Curated mix of reader favourites',
    'Gift-ready packaging',
    'Thoughtful picks in one box',
    'Easy to send to someone special',
  ],
  'just-mini-knots': [
    'Handmade crochet pieces',
    'Perfect hamper add-ons',
    'Small, giftable extras',
    'Made with care in small batches',
  ],
}

export const CATEGORY_DESCRIPTION_FALLBACK: Record<ShopCategory, string> = {
  bookmarks:
    'A magnetic bookmark that hugs your page and never slips — made for readers who hate losing their place. Slim, glossy, and tough enough for daily use.',
  cards:
    'A greeting card with rich paper and a clean layout — ready for your words inside and easy to pair with a small gift.',
  calendars:
    'A desk or wall calendar with a calm, readable layout — mark the year beside your favourite bookmarks and stationery.',
  magnets:
    'A fridge magnet with a glossy finish — perfect for photos, quotes, or little reminders on the kitchen door.',
  hampers:
    'A curated gift box of bookmarks, cards, and small treats — an easy yes when you want something tangible and ready to send.',
  'just-mini-knots':
    'Handmade crochet pieces and little extras — perfect to tuck into a custom hamper or send as a small standalone gift.',
}

export function productDetailFromSiteProduct(p: SiteProduct): ProductDetail {
  const images =
    p.images && p.images.length > 0 ? [...p.images] : p.image ? [p.image] : []
  return {
    ...p,
    description: CATEGORY_DESCRIPTION_FALLBACK[p.category] ?? null,
    features: [...(CATEGORY_FEATURES[p.category] ?? [])],
    images,
  }
}
