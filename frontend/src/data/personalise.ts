import type { ShopCategory } from '@/data/siteContent'

/** Custom product studio — types, sample imagery, and pricing. */

/**
 * Storefront visibility for personalise promos/sections.
 * false = hide homepage block + shop promos; /personalise route still works.
 */
export const PERSONALISE_STOREFRONT_VISIBLE = false

export type PersonaliseType = 'bookmark' | 'calendar' | 'card' | 'magnet'

export interface PersonaliseProduct {
  id: PersonaliseType
  label: string
  shortLabel: string
  blurb: string
  /** Representative product photo for home cards */
  sampleImage: string
  price: number
  compareAt: number
}

export const PERSONALISE_PRODUCTS: PersonaliseProduct[] = [
  {
    id: 'bookmark',
    label: 'Custom magnetic bookmark',
    shortLabel: 'Custom bookmark',
    blurb: 'Your photo and words on a slim fold-over magnetic clip — made to order.',
    sampleImage: '/products/magnetic-bookmarks.jpeg',
    price: 449,
    compareAt: 549,
  },
  {
    id: 'calendar',
    label: 'Custom photo calendar',
    shortLabel: 'Calendars',
    blurb: 'Desk or wall layout with your picture each month.',
    sampleImage:
      '/products/calendars.jpeg',
    price: 599,
    compareAt: 749,
  },
  {
    id: 'card',
    label: 'Custom photo card',
    shortLabel: 'Cards',
    blurb: 'Photo front, your message inside — any occasion.',
    sampleImage:
      '/products/birthday-cards.jpeg',
    price: 399,
    compareAt: 499,
  },
  {
    id: 'magnet',
    label: 'Custom fridge magnet',
    shortLabel: 'Fridge magnets',
    blurb: 'Square photo magnet with a glossy finish.',
    sampleImage:
      '/products/fridge-magnets.jpeg',
    price: 349,
    compareAt: 449,
  },
]

export function personaliseProduct(id: PersonaliseType): PersonaliseProduct {
  const found = PERSONALISE_PRODUCTS.find((p) => p.id === id)
  if (!found) return PERSONALISE_PRODUCTS[0]
  return found
}

export function isPersonaliseType(v: unknown): v is PersonaliseType {
  return v === 'bookmark' || v === 'calendar' || v === 'card' || v === 'magnet'
}

const CATEGORY_TO_PERSONALISE: Partial<Record<ShopCategory, PersonaliseType>> = {
  bookmarks: 'bookmark',
  cards: 'card',
  calendars: 'calendar',
  magnets: 'magnet',
}

/** Shop category → personalise studio product (null for hampers / unsupported). */
export function personaliseTypeForCategory(category: string): PersonaliseType | null {
  if (category in CATEGORY_TO_PERSONALISE) {
    return CATEGORY_TO_PERSONALISE[category as ShopCategory] ?? null
  }
  return null
}

/** Shop category pages — custom product promo (bookmarks, cards, fridge magnets). */
export function personalisePromoForCategory(category: string): PersonaliseProduct | null {
  if (!PERSONALISE_STOREFRONT_VISIBLE) return null
  const type = personaliseTypeForCategory(category)
  if (!type) return null
  return personaliseProduct(type)
}

/** All product types supported in the personalise studio. */
export function personaliseStudioLiveForCategory(_category: string): boolean {
  return true
}

/** Home / marketing cards — bookmark customisation only. */
export function bookmarkPersonaliseCard(): PersonaliseProduct {
  return personaliseProduct('bookmark')
}
