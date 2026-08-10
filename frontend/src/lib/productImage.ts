/**
 * Normalise product image URLs for <img src> (cart, cards, etc.).
 */
import { getApiBaseUrl } from '@/lib/api'
import {
  DEFAULT_PRODUCT_IMAGE,
  isBrokenPlaceholderImage,
  pickProductImageFromPool,
  rewriteLegacyProductImageUrl,
} from '@/data/productImages'

function uploadImageUrl(path: string): string {
  const p = path.startsWith('/') ? path : `/${path}`
  const base = getApiBaseUrl()
  if (base) return `${base}/v1${p}`
  if (typeof window !== 'undefined' && window.location?.origin) {
    return `${window.location.origin}/api/v1${p}`
  }
  return `/api/v1${p}`
}

/** Normalise API product image list (primary first). */
export function resolveProductImages(
  raw: { images?: unknown; image?: unknown; imageUrl?: unknown },
  category = '',
): string[] {
  const rawImages = Array.isArray(raw.images) ? raw.images : []
  const mapped = rawImages
    .map((img) => resolveProductImageUrl(String(img), category))
    .filter(Boolean)
  if (mapped.length > 0) return mapped
  const primary = resolveProductImageUrl(
    String(raw.image ?? raw.imageUrl ?? ''),
    category,
  )
  return primary ? [primary] : []
}

export function resolveProductImageUrl(raw: string | undefined | null, category = ''): string {
  const url = rewriteLegacyProductImageUrl((raw ?? '').trim())
  if (!url) return ''
  if (url.startsWith('blob:') || url.startsWith('data:')) return url
  if (url.startsWith('https://') || url.startsWith('http://')) {
    if (isBrokenPlaceholderImage(url)) {
      return pickProductImageFromPool(url, category || undefined)
    }
    return url
  }
  if (url.startsWith('//')) return `https:${url}`
  if (url.startsWith('/uploads/')) {
    return uploadImageUrl(url)
  }
  if (url.startsWith('/')) {
    if (typeof window !== 'undefined' && window.location?.origin) {
      return `${window.location.origin}${url}`
    }
    return url
  }
  return url
}

/** Like resolveProductImageUrl but always returns a usable src for product cards. */
export function productDisplayImage(raw: string | undefined | null, key = 'product'): string {
  const resolved = resolveProductImageUrl(raw)
  if (resolved) return resolved
  return pickProductImageFromPool(key)
}

export { DEFAULT_PRODUCT_IMAGE }
