/** Catalogue + marketing copy for The Minimark (stationery / bookmarks focus). */

export type ShopCategory =
  | 'bookmarks'
  | 'cards'
  | 'calendars'
  | 'magnets'
  | 'hampers'
  | 'just-mini-knots'

/** Upcoming crochet add-ons — matches admin category slug. */
export const MINI_KNOTS_CATEGORY: ShopCategory = 'just-mini-knots'

export function isShopCategory(v: unknown): v is ShopCategory {
  return (
    v === 'bookmarks' ||
    v === 'cards' ||
    v === 'calendars' ||
    v === 'magnets' ||
    v === 'hampers' ||
    v === 'just-mini-knots'
  )
}

export const SHOP_CATEGORIES: { id: ShopCategory | 'all'; label: string; blurb: string }[] = [
  { id: 'all', label: 'All', blurb: 'Everything in store' },
  { id: 'bookmarks', label: 'Bookmarks', blurb: 'Magnetic & classic clips' },
  { id: 'cards', label: 'Cards', blurb: 'Birthday, thank you & more' },
  { id: 'calendars', label: 'Calendars', blurb: 'Desk & wall' },
  { id: 'magnets', label: 'Fridge Magnets', blurb: 'Fridge & photo magnets' },
  { id: 'hampers', label: 'Hampers', blurb: 'Curated gifts' },
  { id: 'just-mini-knots', label: 'Just Mini Knots', blurb: 'Handmade crochet add-ons' },
]

export interface SiteProduct {
  id: string
  slug: string
  name: string
  image: string
  /** All product photos (primary first). From API when available. */
  images?: string[]
  price: number
  compareAt: number
  category: ShopCategory
  /** Subcategory slug within category (from API). */
  subcategory?: string
  /** Present when loaded from the PHP API (drives home sections). */
  homeBestseller?: boolean
  homeSecondary?: boolean
}

export const announcement =
  'Free delivery above ₹499 · Use code MINIFIRST10 for 10% off your first order · Returns within 24 hrs with unboxing video'

export const trustItems = [
  {
    title: 'Free Delivery',
    text: 'Delivery is free above ₹499 on every order.',
    icon: 'truck',
  },
  {
    title: 'Flexible Payment',
    text: 'Pay with secure & easy flexible payment options.',
    icon: 'payment',
  },
  {
    title: '10% off your first order',
    text: 'Use code MINIFIRST10 for 10% off on your first order.',
    icon: 'offer',
  },
  {
    title: 'Return & Refund',
    text: 'Return accepted within 24 hours with an unboxing video.',
    icon: 'return',
  },
] as const

export const howItWorks = [
  {
    step: '1',
    title: 'Browse by category',
    text: 'Bookmarks, cards, calendars, magnets — filter the shop to match what you need.',
    ctaLabel: 'Browse shop',
    ctaTo: '/shop',
  },
  {
    step: '2',
    title: 'Add to cart',
    text: 'Clear prices, sale items marked, cart saves on your device while you shop.',
    ctaLabel: 'View cart',
    ctaTo: '/cart',
  },
  {
    step: '3',
    title: 'Checkout & enjoy',
    text: 'Complete your order securely. We pack with care and ship as fast as we can.',
    ctaLabel: 'Go to checkout',
    ctaTo: '/checkout',
  },
] as const

export const howItWorksIntro = {
  eyebrow: 'How it works',
  title: 'Shop in three simple steps',
  description:
    'The same flow shoppers expect from modern stationery stores: browse, cart, checkout — without clutter.',
} as const

export const newsletterSection = {
  eyebrow: 'Newsletter',
  title: 'Get offers & new arrivals',
  description: 'Occasional emails — no spam. Unsubscribe anytime.',
  placeholder: 'Your email',
  buttonLabel: 'Subscribe',
  finePrint: 'We respect your inbox. No third-party ads.',
} as const

/** Hero carousel slides (first is the canonical “primary” hero for backwards use). */
export const heroSlides = [
  {
    eyebrow: 'New season',
    tabLabel: 'Bookmarks',
    title: 'Magnetic clips that stay on every page',
    text: 'Fold-over bookmarks, classic styles, and custom photo pieces — made for readers who never lose their place.',
    image: '/products/magnetic-bookmarks.jpeg',
    ctaPrimary: { label: 'Shop now', to: '/shop/bookmarks' },
    ctaSecondary: { label: 'Explore More', to: '/shop' },
  },
  {
    eyebrow: 'Greeting cards',
    tabLabel: 'Cards',
    title: 'Say it beautifully for every occasion',
    text: 'Birthday, thank you, and love cards with rich papers — ready to post or pair with a small gift.',
    image: '/products/birthday-cards.jpeg',
    ctaPrimary: { label: 'Shop now', to: '/shop/cards' },
    ctaSecondary: { label: 'Explore More', to: '/shop' },
  },
  {
    eyebrow: 'Desk & wall',
    tabLabel: 'Calendars',
    title: 'Mark the year with calm, clear layouts',
    text: 'Desk and wall calendars that sit neatly beside your favourite bookmarks and stationery.',
    image: '/products/calendars.jpeg',
    ctaPrimary: { label: 'Shop now', to: '/shop/calendars' },
    ctaSecondary: { label: 'Explore More', to: '/shop' },
  },
  {
    eyebrow: 'Fridge & desk',
    tabLabel: 'Fridge Magnets',
    title: 'Photo magnets for your fridge & locker',
    text: 'Glossy custom photo magnets and quote pieces — gift-ready picks that stick around.',
    image: '/products/fridge-magnets.jpeg',
    ctaPrimary: { label: 'Shop now', to: '/shop/magnets' },
    ctaSecondary: { label: 'Explore More', to: '/shop' },
  },
] as const

/** @deprecated Prefer `heroSlides` — kept for any code expecting a single hero object. */
export const heroPrimary = heroSlides[0]

export const categoryStrip = [
  {
    title: 'Bookmarks',
    blurb: 'Magnetic & classic',
    href: '/shop/bookmarks',
    image: '/products/magnetic-bookmarks.jpeg',
  },
  {
    title: 'Cards',
    blurb: 'Birthday & thank you',
    href: '/shop/cards',
    image: '/products/birthday-cards.jpeg',
  },
  {
    title: 'Fridge Magnets',
    blurb: 'Photo & quote magnets',
    href: '/shop/magnets',
    image: '/products/fridge-magnets.jpeg',
  },
  {
    title: 'Calendars',
    blurb: 'Desk & wall',
    href: '/shop/calendars',
    image: '/products/calendars.jpeg',
  },
  {
    title: 'Hampers',
    blurb: 'Curated gift boxes',
    href: '/shop/hampers',
    image: '/products/hampers.jpeg',
  },
] as const

/** Home bestsellers grid: 5 columns × 2 rows on desktop. */
export const HOME_BESTSELLER_LIMIT = 10

export const favouritesProducts: SiteProduct[] = [
  {
    id: '7345',
    slug: 'magnetic-bookmarks',
    name: 'Magnetic Bookmarks',
    image:
      '/products/magnetic-bookmarks.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '7356',
    slug: 'classic-bookmarks',
    name: 'Classic Bookmarks',
    image:
      '/products/classic-bookmarks.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '7357',
    slug: 'birthday-cards',
    name: 'Birthday Cards',
    image:
      '/products/birthday-cards.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7358',
    slug: 'thank-you-cards',
    name: 'Thank You Cards',
    image:
      '/products/thank-you-cards.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7359',
    slug: 'love-cards',
    name: 'Love Cards',
    image:
      '/products/love-cards.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7360',
    slug: 'sorry-cards',
    name: 'Sorry Cards',
    image:
      '/products/sorry-cards.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7361',
    slug: 'hampers',
    name: 'Hampers',
    image:
      '/products/hampers.jpeg',
    price: 399,
    compareAt: 499,
    category: 'hampers',
  },
  {
    id: '7362',
    slug: 'mini-hamper',
    name: 'Mini Hamper',
    image: '/products/mini-hamper.jpeg',
    price: 399,
    compareAt: 499,
    category: 'hampers',
  },
  {
    id: '7363',
    slug: 'fridge-magnets',
    name: 'Fridge Magnets',
    image: '/products/fridge-magnets.jpeg',
    price: 399,
    compareAt: 499,
    category: 'magnets',
  },
  {
    id: '7365',
    slug: 'couple-fridge-magnets',
    name: 'Couple Fridge Magnets',
    image:
      '/products/couple-fridge-magnets.jpeg',
    price: 399,
    compareAt: 499,
    category: 'magnets',
  },
]

/** Home carousel — magnetic bookmarks only. */
export const magneticBookmarkProducts: SiteProduct[] = [
  {
    id: '7345',
    slug: 'magnetic-bookmarks',
    name: 'Magnetic Bookmarks',
    image:
      '/products/magnetic-bookmarks.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '8101',
    slug: 'magnetic-bookmark-whimsical-set',
    name: 'Whimsical Magnetic Bookmark Set',
    image:
      '/products/classic-bookmarks.jpeg',
    price: 449,
    compareAt: 549,
    category: 'bookmarks',
  },
  {
    id: '8102',
    slug: 'magnetic-bookmark-floral',
    name: 'Floral Magnetic Bookmark',
    image: '/products/magnetic-bookmarks.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '8103',
    slug: 'magnetic-bookmark-animal-friends',
    name: 'Animal Friends Magnetic Bookmark',
    image: '/products/magnetic-bookmarks.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '8104',
    slug: 'magnetic-bookmark-literary-quotes',
    name: 'Literary Quotes Magnetic Bookmark',
    image: '/products/magnetic-bookmarks.jpeg',
    price: 379,
    compareAt: 479,
    category: 'bookmarks',
  },
  {
    id: '8105',
    slug: 'magnetic-bookmark-minimal-line',
    name: 'Minimal Line Magnetic Bookmark',
    image: '/products/magnetic-bookmarks.jpeg',
    price: 349,
    compareAt: 449,
    category: 'bookmarks',
  },
  {
    id: '8106',
    slug: 'magnetic-bookmark-vintage-maps',
    name: 'Vintage Maps Magnetic Bookmark',
    image: '/products/magnetic-bookmarks.jpeg',
    price: 429,
    compareAt: 529,
    category: 'bookmarks',
  },
  {
    id: '8107',
    slug: 'magnetic-bookmark-watercolor',
    name: 'Watercolor Magnetic Bookmark',
    image: '/products/magnetic-bookmarks.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
]

export type BookmarkType = 'classic' | 'magnetic'

export function matchesBookmarkType(
  product: Pick<SiteProduct, 'category' | 'slug' | 'name'>,
  type: BookmarkType
): boolean {
  if (product.category !== 'bookmarks') return false
  const hay = `${product.slug} ${product.name}`.toLowerCase()
  if (type === 'magnetic') return hay.includes('magnetic')
  return hay.includes('classic') || !hay.includes('magnetic')
}

export function categoryLabel(cat: ShopCategory): string {
  const m: Record<ShopCategory, string> = {
    bookmarks: 'Bookmarks',
    cards: 'Cards',
    calendars: 'Calendars',
    magnets: 'Magnets',
    hampers: 'Hampers',
    'just-mini-knots': 'Just Mini Knots',
  }
  return m[cat]
}
