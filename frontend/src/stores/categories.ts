import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, getApiBaseUrl } from '@/lib/api'
import {
  staticCategories,
  STATIC_SUBCATEGORIES,
  type ShopCategoryWithSubs,
  type ShopSubcategory,
} from '@/types/shopCategory'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

function mapCategory(raw: Record<string, unknown>): ShopCategoryWithSubs {
  const subs = Array.isArray(raw.subcategories) ? raw.subcategories : []
  return {
    id: Number(raw.id),
    slug: String(raw.slug),
    name: String(raw.name),
    description: String(raw.description ?? ''),
    keywords: String(raw.keywords ?? ''),
    imagePath: String(raw.imagePath ?? ''),
    imageUrl: String(raw.imageUrl ?? ''),
    sortOrder: Number(raw.sortOrder ?? 0),
    isActive: Boolean(raw.isActive ?? true),
    productCount: raw.productCount !== undefined ? Number(raw.productCount) : undefined,
    subcategories: subs.map((s) => {
      const row = s as Record<string, unknown>
      return {
        id: Number(row.id),
        categorySlug: String(row.categorySlug ?? raw.slug),
        slug: String(row.slug),
        name: String(row.name),
        sortOrder: Number(row.sortOrder ?? 0),
        isActive: Boolean(row.isActive ?? true),
      }
    }),
  }
}

export const useCategoriesStore = defineStore('categories', () => {
  const categories = ref<ShopCategoryWithSubs[]>(staticCategories())
  const ready = ref(false)
  let inflight: Promise<void> | null = null

  async function ensureLoaded(): Promise<void> {
    if (ready.value) return
    if (inflight) {
      await inflight
      return
    }
    inflight = (async () => {
      try {
        const data = await apiFetch<{ items: unknown[] }>(`${apiPrefix()}/categories`)
        const items = Array.isArray(data.items) ? data.items : []
        if (items.length > 0) {
          categories.value = items.map((row) => mapCategory(row as Record<string, unknown>))
        }
      } catch {
        categories.value = staticCategories()
      } finally {
        ready.value = true
        inflight = null
      }
    })()
    await inflight
  }

  function subcategoriesFor(categorySlug: string): ShopSubcategory[] {
    const cat = categories.value.find((c) => c.slug === categorySlug)
    if (cat?.subcategories?.length) return cat.subcategories.filter((s) => s.isActive)
    return STATIC_SUBCATEGORIES[categorySlug] ?? []
  }

  function subcategoryLabel(categorySlug: string, subSlug: string): string {
    const sub = subcategoriesFor(categorySlug).find((s) => s.slug === subSlug)
    return sub?.name ?? subSlug
  }

  return { categories, ready, ensureLoaded, subcategoriesFor, subcategoryLabel }
})
