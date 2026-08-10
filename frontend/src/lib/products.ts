import { ApiError, apiFetch, getApiBaseUrl } from '@/lib/api'
import { resolveProductImageUrl, resolveProductImages } from '@/lib/productImage'
import { isShopCategory, type SiteProduct } from '@/data/siteContent'
import {
  productDetailFromSiteProduct,
  type ProductDetail,
} from '@/types/productDetail'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

function mapProductDetail(raw: Record<string, unknown>): ProductDetail | null {
  const category = raw.category
  if (!isShopCategory(category)) return null

  const id = String(raw.id)
  const slug = String(raw.slug)
  const categoryStr = String(category)
  const fallback = resolveProductImageUrl(
    String(raw.image ?? raw.imageUrl ?? ''),
    categoryStr,
  )

  const images = resolveProductImages(raw, categoryStr)
  if (images.length === 0 && fallback) images.push(fallback)

  const descriptionRaw = raw.description
  const description =
    typeof descriptionRaw === 'string' && descriptionRaw.trim() !== ''
      ? descriptionRaw.trim()
      : null

  const featuresRaw = raw.features
  const features = Array.isArray(featuresRaw)
    ? featuresRaw.map((f) => String(f).trim()).filter(Boolean)
    : []

  return {
    id,
    slug,
    name: String(raw.name),
    image: images[0] ?? fallback,
    price: Number(raw.price),
    compareAt: Number(raw.compareAt ?? raw.compare_at ?? raw.price),
    category,
    homeBestseller: Boolean(raw.homeBestseller),
    homeSecondary: Boolean(raw.homeSecondary),
    description,
    features,
    images,
  }
}

export async function fetchProductBySlug(
  slug: string,
  fallback?: SiteProduct | null,
): Promise<ProductDetail | null> {
  try {
    const data = await apiFetch<Record<string, unknown>>(
      `${apiPrefix()}/products/${encodeURIComponent(slug)}`,
    )
    const mapped = mapProductDetail(data)
    if (mapped) return mapped
  } catch (e) {
    if (e instanceof ApiError && e.status === 404) {
      if (fallback) return productDetailFromSiteProduct(fallback)
      return null
    }
    if (fallback) return productDetailFromSiteProduct(fallback)
    throw e
  }
  if (fallback) return productDetailFromSiteProduct(fallback)
  return null
}

export function productRoute(slug: string) {
  return { name: 'product' as const, params: { slug } }
}
