import type { SiteProduct } from '@/data/siteContent'
import { matchesBookmarkType } from '@/data/siteContent'
import type { HomeProductSegmentBase } from '@/types/homePageLayout'

export function resolveHomeProducts(
  segment: HomeProductSegmentBase,
  catalog: SiteProduct[],
  favourites: SiteProduct[],
  magneticBookmarks: SiteProduct[],
): SiteProduct[] {
  const limit = Math.min(24, Math.max(1, segment.limit ?? 8))
  const ids = segment.productIds ?? []

  switch (segment.source) {
    case 'bestsellers':
      return diversifyBestsellers(favourites, catalog, limit)
    case 'magnetic':
      return magneticBookmarks.slice(0, limit)
    case 'secondary':
      return catalog.filter((p) => p.homeSecondary).slice(0, limit)
    case 'category': {
      const cat = segment.category
      if (!cat) return catalog.slice(0, limit)
      return catalog
        .filter(
          (p) =>
            p.category === cat &&
            (!segment.subcategory || p.subcategory === segment.subcategory),
        )
        .slice(0, limit)
    }
    case 'custom':
      return ids
        .map((id) => catalog.find((p) => p.id === id))
        .filter((p): p is SiteProduct => Boolean(p))
    case 'sale':
      return catalog
        .filter((p) => p.compareAt != null && p.compareAt > p.price)
        .slice(0, limit)
    default:
      return catalog.slice(0, limit)
  }
}

/** Prefer a mixed category spread so bestsellers are not bookmark-only. */
function diversifyBestsellers(
  favourites: SiteProduct[],
  catalog: SiteProduct[],
  limit: number,
): SiteProduct[] {
  const seeded = favourites.length > 0 ? favourites : catalog
  const distinctCats = new Set(seeded.map((p) => p.category))
  let pool = seeded
  if (distinctCats.size <= 1 && catalog.length > seeded.length) {
    const extras = catalog.filter((p) => !seeded.some((s) => s.id === p.id))
    pool = [...seeded, ...extras]
  }

  const byCategory = new Map<string, SiteProduct[]>()
  for (const p of pool) {
    const list = byCategory.get(p.category) ?? []
    list.push(p)
    byCategory.set(p.category, list)
  }
  const cats = [...byCategory.keys()]
  if (cats.length <= 1) return pool.slice(0, limit)

  const out: SiteProduct[] = []
  const seen = new Set<string>()
  let i = 0
  while (out.length < limit) {
    let added = false
    for (const cat of cats) {
      const list = byCategory.get(cat) ?? []
      const candidate = list[i]
      if (candidate && !seen.has(candidate.id)) {
        out.push(candidate)
        seen.add(candidate.id)
        added = true
        if (out.length >= limit) break
      }
    }
    if (!added) break
    i += 1
  }
  if (out.length < limit) {
    for (const p of pool) {
      if (seen.has(p.id)) continue
      out.push(p)
      if (out.length >= limit) break
    }
  }
  return out
}

export function filterMagnetic(products: SiteProduct[]): SiteProduct[] {
  return products.filter((p) => matchesBookmarkType(p, 'magnetic'))
}
