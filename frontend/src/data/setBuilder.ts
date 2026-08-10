/** Create Your Own Set — bookmark bundles & build-a-hamper. */

export type SetMode = 'bookmarks' | 'hamper'

export const BOOKMARK_SET_SIZES = [2, 4, 6, 8] as const
export type BookmarkSetSize = (typeof BOOKMARK_SET_SIZES)[number]

/** @deprecated use BOOKMARK_SET_SIZES — kept for older imports */
export const SET_SIZE_OPTIONS = BOOKMARK_SET_SIZES
/** @deprecated use BookmarkSetSize */
export type SetSize = BookmarkSetSize

/** Soft cap so 8+ sets can grow freely without breaking the UI. */
export const BOOKMARK_OPEN_MAX = 48

export const BOOKMARK_SET_TIERS: {
  size: BookmarkSetSize
  discountPercent: number
  label: string
  sizeLabel: string
  /** When true, user may pick size or more items (8+). */
  openEnded?: boolean
}[] = [
  { size: 2, discountPercent: 5, label: '5% off', sizeLabel: 'Set of 2' },
  { size: 4, discountPercent: 10, label: '10% off', sizeLabel: 'Set of 4' },
  { size: 6, discountPercent: 15, label: '15% off', sizeLabel: 'Set of 6' },
  { size: 8, discountPercent: 20, label: '20% off', sizeLabel: 'Set of 8+', openEnded: true },
]

/** @deprecated use BOOKMARK_SET_TIERS */
export const SET_DISCOUNT_TIERS = BOOKMARK_SET_TIERS

export const HAMPER_MIN_ITEMS = 4
export const HAMPER_MAX_ITEMS = 24
/** 4–9 items → 10%; 10+ → 18% */
export const HAMPER_TIER_NOTES = [
  { min: 4, discountPercent: 10, label: '4+ items · 10% off' },
  { min: 10, discountPercent: 18, label: '10+ items · 18% off' },
] as const

export function isBookmarkOpenEnded(size: BookmarkSetSize): boolean {
  return BOOKMARK_SET_TIERS.find((t) => t.size === size)?.openEnded === true
}

export function bookmarkMinForTier(size: BookmarkSetSize): number {
  return size
}

export function bookmarkMaxForTier(size: BookmarkSetSize): number {
  return isBookmarkOpenEnded(size) ? BOOKMARK_OPEN_MAX : size
}

export function bookmarkDiscountForSize(size: BookmarkSetSize): number {
  return BOOKMARK_SET_TIERS.find((t) => t.size === size)?.discountPercent ?? 0
}

/** @deprecated use bookmarkDiscountForSize */
export function discountForSetSize(size: BookmarkSetSize): number {
  return bookmarkDiscountForSize(size)
}

export function hamperDiscountForCount(count: number): number {
  if (count >= 10) return 18
  if (count >= HAMPER_MIN_ITEMS) return 10
  return 0
}

export function applyDiscount(price: number, discountPercent: number): number {
  if (discountPercent <= 0) return price
  return Math.round(price * (1 - discountPercent / 100))
}

export function discountedPrice(price: number, size: BookmarkSetSize): number {
  return applyDiscount(price, bookmarkDiscountForSize(size))
}

export function setModeFromQuery(raw: unknown): SetMode {
  return raw === 'hamper' ? 'hamper' : 'bookmarks'
}
