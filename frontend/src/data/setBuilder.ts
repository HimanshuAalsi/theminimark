/** Even-sized custom sets — bigger sets earn a bigger discount. */
export const SET_SIZE_OPTIONS = [2, 4, 6, 8] as const

export type SetSize = (typeof SET_SIZE_OPTIONS)[number]

export const SET_DISCOUNT_TIERS: { size: SetSize; discountPercent: number; label: string }[] = [
  { size: 2, discountPercent: 5, label: '5% off' },
  { size: 4, discountPercent: 10, label: '10% off' },
  { size: 6, discountPercent: 15, label: '15% off' },
  { size: 8, discountPercent: 20, label: '20% off' },
]

export function discountForSetSize(size: SetSize): number {
  return SET_DISCOUNT_TIERS.find((t) => t.size === size)?.discountPercent ?? 0
}

export function discountedPrice(price: number, size: SetSize): number {
  const pct = discountForSetSize(size)
  return Math.round(price * (1 - pct / 100))
}
