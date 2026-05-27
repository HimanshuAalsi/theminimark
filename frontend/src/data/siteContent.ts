/** Catalogue + marketing copy for The Minimark (stationery / bookmarks focus). */

export type ShopCategory = 'bookmarks' | 'cards' | 'calendars' | 'magnets' | 'hampers'

export const SHOP_CATEGORIES: { id: ShopCategory | 'all'; label: string; blurb: string }[] = [
  { id: 'all', label: 'All', blurb: 'Everything in store' },
  { id: 'bookmarks', label: 'Bookmarks', blurb: 'Magnetic & classic clips' },
  { id: 'cards', label: 'Cards', blurb: 'Birthday, thank you & more' },
  { id: 'calendars', label: 'Calendars', blurb: 'Desk & wall' },
  { id: 'magnets', label: 'Magnets', blurb: 'Fridge & photo magnets' },
  { id: 'hampers', label: 'Hampers', blurb: 'Curated gifts' },
]

export interface SiteProduct {
  id: string
  slug: string
  name: string
  image: string
  price: number
  compareAt: number
  category: ShopCategory
  /** Present when loaded from the PHP API (drives home sections). */
  homeBestseller?: boolean
  homeSecondary?: boolean
}

export const announcement =
  'Free standard shipping on orders over ₹999 · Easy returns within 14 days · Secure checkout'

export const trustItems = [
  {
    title: 'Free shipping $50+',
    text: 'Straightforward delivery on bigger baskets.',
    icon: 'truck',
  },
  {
    title: 'Easy returns',
    text: '14 days if something isn’t right.',
    icon: 'return',
  },
  {
    title: 'Secure checkout',
    text: 'Encrypted payments you can trust.',
    icon: 'lock',
  },
  {
    title: 'We’re here to help',
    text: 'Quick answers on orders & products.',
    icon: 'chat',
  },
] as const

export const howItWorks = [
  {
    step: '1',
    title: 'Browse by category',
    text: 'Bookmarks, cards, calendars, magnets — filter the shop to match what you need.',
  },
  {
    step: '2',
    title: 'Add to cart',
    text: 'Clear prices, sale items marked, cart saves on your device while you shop.',
  },
  {
    step: '3',
    title: 'Checkout & enjoy',
    text: 'Complete your order securely. We pack with care and ship as fast as we can.',
  },
] as const

/** Hero carousel slides (first is the canonical “primary” hero for backwards use). */
export const heroSlides = [
  {
    eyebrow: 'New season',
    tabLabel: 'Bookmarks',
    title: 'Magnetic clips that stay on every page',
    text: 'Fold-over bookmarks, classic styles, and custom photo pieces — made for readers who never lose their place.',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg',
    ctaPrimary: { label: 'Shop bookmarks', to: '/shop?category=bookmarks' },
    ctaSecondary: { label: 'Create your set', to: '/create-your-set' },
  },
  {
    eyebrow: 'Greeting cards',
    tabLabel: 'Cards',
    title: 'Say it beautifully for every occasion',
    text: 'Birthday, thank you, and love cards with rich papers — ready to post or pair with a small gift.',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Panda-Pun-Birthday-Card-Have-a-panda-stic-Birthday-Panda-Birthday-Card-Birthday-Card-for-Friend-Birthday-Card-for-Nephew-Niece-700x700.jpeg',
    ctaPrimary: { label: 'Browse cards', to: '/shop?category=cards' },
    ctaSecondary: { label: 'Personalise', to: '/personalise' },
  },
  {
    eyebrow: 'Desk & wall',
    tabLabel: 'Calendars',
    title: 'Mark the year with calm, clear layouts',
    text: 'Desk and wall calendars that sit neatly beside your favourite bookmarks and stationery.',
    image: 'https://theminimark.com/wp-content/uploads/2026/03/download-44-700x700.jpeg',
    ctaPrimary: { label: 'Shop calendars', to: '/shop?category=calendars' },
    ctaSecondary: { label: 'View shop', to: '/shop' },
  },
  {
    eyebrow: 'Gift ready',
    tabLabel: 'Hampers',
    title: 'Curated boxes for readers & gifters',
    text: 'Thoughtful hampers and fridge magnets — easy picks when you want something tangible and quick to send.',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Visit-Now_-Customize-a-Birthday-Hamper-for-Your-Bestie-700x700.jpeg',
    ctaPrimary: { label: 'Shop hampers', to: '/shop?category=hampers' },
    ctaSecondary: { label: 'Fridge magnets', to: '/shop?category=magnets' },
  },
] as const

/** @deprecated Prefer `heroSlides` — kept for any code expecting a single hero object. */
export const heroPrimary = heroSlides[0]

export const categoryStrip = [
  {
    title: 'Bookmarks',
    blurb: 'Magnetic & classic',
    href: '/shop?category=bookmarks',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg',
  },
  {
    title: 'Cards',
    blurb: 'Birthday & thank you',
    href: '/shop?category=cards',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Panda-Pun-Birthday-Card-Have-a-panda-stic-Birthday-Panda-Birthday-Card-Birthday-Card-for-Friend-Birthday-Card-for-Nephew-Niece-700x700.jpeg',
  },
  {
    title: 'Fridge Magnets',
    blurb: 'Photo & quote magnets',
    href: '/shop?category=magnets',
    image: 'https://theminimark.com/wp-content/uploads/2026/03/download-43-700x700.jpeg',
  },
  {
    title: 'Calendars',
    blurb: 'Desk & wall',
    href: '/shop?category=calendars',
    image: 'https://theminimark.com/wp-content/uploads/2026/03/download-44-700x700.jpeg',
  },
  {
    title: 'Hampers',
    blurb: 'Curated gift boxes',
    href: '/shop?category=hampers',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Visit-Now_-Customize-a-Birthday-Hamper-for-Your-Bestie-700x700.jpeg',
  },
  {
    title: 'Gift combos',
    blurb: 'Ready-made sets',
    href: '/shop',
    image: 'https://theminimark.com/wp-content/uploads/2026/03/gift-hamper-for-her-700x700.jpeg',
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
      'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '7356',
    slug: 'classic-bookmarks',
    name: 'Classic Bookmarks',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Whimsical-Bookmark-Collection_-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '7357',
    slug: 'birthday-cards',
    name: 'Birthday Cards',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Panda-Pun-Birthday-Card-Have-a-panda-stic-Birthday-Panda-Birthday-Card-Birthday-Card-for-Friend-Birthday-Card-for-Nephew-Niece-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7358',
    slug: 'thank-you-cards',
    name: 'Thank You Cards',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Set-of-3-thank-you-cards-two-toned-theme-thank-you-card-pack-handmade-thank-you-cards-card-assortment-thank-you-card-variety-pack-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7359',
    slug: 'love-cards',
    name: 'Love Cards',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Youre-My-Favourite-Person-Card-_-Valentines-Card-_-Be-My-Valentine-_-Love-You-Card-_-Valentine-Card-_-Watercolour-Hearts-Card-_-With-Love-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7360',
    slug: 'sorry-cards',
    name: 'Sorry Cards',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Im-Sorry-Card-Printable_-Rewind-Cassette-Tape-Design-digital-Download-Etsy-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'cards',
  },
  {
    id: '7361',
    slug: 'hampers',
    name: 'Hampers',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Visit-Now_-Customize-a-Birthday-Hamper-for-Your-Bestie-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'hampers',
  },
  {
    id: '7362',
    slug: 'mini-hamper',
    name: 'Mini Hamper',
    image: 'https://theminimark.com/wp-content/uploads/2026/03/gift-hamper-for-her-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'hampers',
  },
  {
    id: '7363',
    slug: 'fridge-magnets',
    name: 'Fridge Magnets',
    image: 'https://theminimark.com/wp-content/uploads/2026/03/download-43-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'magnets',
  },
  {
    id: '7365',
    slug: 'couple-fridge-magnets',
    name: 'Couple Fridge Magnets',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Personalisierte-Save-the-Date-Kuhlschrankmagnet-Kalender-Hochzeit-Einladung-Ankundigung-Geschenk-700x700.jpeg',
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
      'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '8101',
    slug: 'magnetic-bookmark-whimsical-set',
    name: 'Whimsical Magnetic Bookmark Set',
    image:
      'https://theminimark.com/wp-content/uploads/2026/03/Whimsical-Bookmark-Collection_-700x700.jpeg',
    price: 449,
    compareAt: 549,
    category: 'bookmarks',
  },
  {
    id: '8102',
    slug: 'magnetic-bookmark-floral',
    name: 'Floral Magnetic Bookmark',
    image: 'https://picsum.photos/seed/magnetic-bookmark-floral-8102/700/700',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '8103',
    slug: 'magnetic-bookmark-animal-friends',
    name: 'Animal Friends Magnetic Bookmark',
    image: 'https://picsum.photos/seed/magnetic-bookmark-animal-8103/700/700',
    price: 399,
    compareAt: 499,
    category: 'bookmarks',
  },
  {
    id: '8104',
    slug: 'magnetic-bookmark-literary-quotes',
    name: 'Literary Quotes Magnetic Bookmark',
    image: 'https://picsum.photos/seed/magnetic-bookmark-quotes-8104/700/700',
    price: 379,
    compareAt: 479,
    category: 'bookmarks',
  },
  {
    id: '8105',
    slug: 'magnetic-bookmark-minimal-line',
    name: 'Minimal Line Magnetic Bookmark',
    image: 'https://picsum.photos/seed/magnetic-bookmark-minimal-8105/700/700',
    price: 349,
    compareAt: 449,
    category: 'bookmarks',
  },
  {
    id: '8106',
    slug: 'magnetic-bookmark-vintage-maps',
    name: 'Vintage Maps Magnetic Bookmark',
    image: 'https://picsum.photos/seed/magnetic-bookmark-vintage-8106/700/700',
    price: 429,
    compareAt: 529,
    category: 'bookmarks',
  },
  {
    id: '8107',
    slug: 'magnetic-bookmark-watercolor',
    name: 'Watercolor Magnetic Bookmark',
    image: 'https://picsum.photos/seed/magnetic-bookmark-watercolor-8107/700/700',
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
  }
  return m[cat]
}
