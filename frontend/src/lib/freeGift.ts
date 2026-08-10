import type { SiteProduct } from '@/data/siteContent'
import { resolveProductImageUrl } from '@/lib/productImage'

export const FREE_GIFT_OPTION_COUNT = 4

export interface FreeGiftOption {
  id: string
  name: string
  image: string
}

/** Build free-gift choices from admin-configured product IDs. */
export function pickConfiguredFreeGifts(
  catalog: SiteProduct[],
  productIds: string[],
  excludeProductIds: Iterable<string | number>,
  count = FREE_GIFT_OPTION_COUNT,
): FreeGiftOption[] {
  const exclude = new Set([...excludeProductIds].map(String))
  const byId = new Map(catalog.map((p) => [String(p.id), p]))
  const out: FreeGiftOption[] = []
  for (const id of productIds) {
    if (exclude.has(String(id))) continue
    const p = byId.get(String(id))
    if (p) out.push(siteProductToFreeGiftOption(p))
    if (out.length >= count) break
  }
  return out.slice(0, count)
}

/** Pick random catalog products as free-gift choices (excludes items already in cart). */
export function pickRandomFreeGifts(
  products: SiteProduct[],
  excludeProductIds: Iterable<string | number>,
  count = FREE_GIFT_OPTION_COUNT,
): FreeGiftOption[] {
  const exclude = new Set([...excludeProductIds].map(String))
  const pool = products.filter((p) => p.id && !exclude.has(String(p.id)))
  if (pool.length === 0) return []

  const shuffled = [...pool]
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]]
  }

  return shuffled.slice(0, count).map((p) => ({
    id: String(p.id),
    name: p.name,
    image: resolveProductImageUrl(p.image),
  }))
}

export function siteProductToFreeGiftOption(product: SiteProduct): FreeGiftOption {
  return {
    id: String(product.id),
    name: product.name,
    image: resolveProductImageUrl(product.image),
  }
}

/** True if this gift can still be claimed (in catalog, not already in cart). */
export function isFreeGiftEligible(
  gift: FreeGiftOption,
  catalog: SiteProduct[],
  excludeProductIds: Iterable<string | number>,
): boolean {
  const exclude = new Set([...excludeProductIds].map(String))
  if (exclude.has(String(gift.id))) return false
  return catalog.some((p) => String(p.id) === String(gift.id))
}

/** Keep the chosen gift visible when refreshing random options. */
export function mergeFreeGiftOptions(
  options: FreeGiftOption[],
  selected: FreeGiftOption | null,
  max = FREE_GIFT_OPTION_COUNT + 1,
): FreeGiftOption[] {
  if (!selected) return options
  const rest = options.filter((o) => o.id !== selected.id)
  return [selected, ...rest].slice(0, max)
}

export function formatFreeGiftOrderNote(gift: FreeGiftOption): string {
  return `Free gift chosen: ${gift.name} (product ${gift.id})`
}
