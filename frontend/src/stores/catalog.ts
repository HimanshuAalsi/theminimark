import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiFetch, getApiBaseUrl } from '@/lib/api'
import {
  favouritesProducts,
  HOME_BESTSELLER_LIMIT,
  magneticBookmarkProducts,
  matchesBookmarkType,
  type ShopCategory,
  type SiteProduct,
} from '@/data/siteContent'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

function isShopCategory(v: unknown): v is ShopCategory {
  return (
    v === 'bookmarks' ||
    v === 'cards' ||
    v === 'calendars' ||
    v === 'magnets' ||
    v === 'hampers'
  )
}

function mapApiProduct(raw: Record<string, unknown>): SiteProduct | null {
  const category = raw.category
  if (!isShopCategory(category)) {
    return null
  }
  return {
    id: String(raw.id),
    slug: String(raw.slug),
    name: String(raw.name),
    image: String(raw.image ?? raw.imageUrl ?? ''),
    price: Number(raw.price),
    compareAt: Number(raw.compareAt ?? raw.compare_at ?? raw.price),
    category,
    homeBestseller: Boolean(raw.homeBestseller),
    homeSecondary: Boolean(raw.homeSecondary),
  }
}

function staticCatalog(): SiteProduct[] {
  const map = new Map<string, SiteProduct>()
  for (const p of favouritesProducts) map.set(p.id, p)
  for (const p of magneticBookmarkProducts) map.set(p.id, p)
  return [...map.values()]
}

const DEV_DB_HELP =
  'No database on this PC — the storefront is using demo products. Choose one: (1) Install Docker Desktop, restart the terminal, run `npm run dev:db` from the repo root, keep `backend/api/config.local.php` on port 3307 and password `root`. (2) Install MariaDB or XAMPP (MySQL on port 3306), create database `theminimark`, import `backend/sql/schema.sql` then `backend/sql/seed.sql`, set `config.local.php` to port 3306 and your MySQL password (often empty for local root). Run `npm run check:db` for a quick port check.'

function devCatalogErrorMessage(e: unknown): string {
  if (e instanceof ApiError) {
    if (e.status === 503 && /database unavailable|mysql did not accept|mariadb/i.test(e.message)) {
      return DEV_DB_HELP
    }
    return e.message
  }
  return 'Could not load products from the API. Using the built-in demo list.'
}

export const useCatalogStore = defineStore('catalog', () => {
  const source = ref<'api' | 'static'>('static')
  const products = ref<SiteProduct[]>([])
  const loading = ref(false)
  const ready = ref(false)
  /** Dev-only hint when the API is down; storefront still uses bundled demo products. */
  const devLoadNotice = ref<string | null>(null)
  let inflight: Promise<void> | null = null

  async function ensureLoaded(options?: { refresh?: boolean }): Promise<void> {
    const refresh = Boolean(options?.refresh)
    if (!refresh && ready.value) return
    if (inflight) {
      await inflight
      if (!refresh && ready.value) return
    }

    inflight = (async () => {
      if (refresh) {
        ready.value = false
        products.value = []
        source.value = 'static'
      }
      loading.value = true
      try {
        devLoadNotice.value = null
        const data = await apiFetch<{ items: unknown[] }>(`${apiPrefix()}/products`)
        const items = Array.isArray(data.items) ? data.items : []
        const mapped = items
          .map((row) => mapApiProduct(row as Record<string, unknown>))
          .filter((p): p is SiteProduct => p !== null)
        if (mapped.length === 0) {
          source.value = 'static'
          products.value = []
        } else {
          products.value = mapped
          source.value = 'api'
        }
      } catch (e) {
        source.value = 'static'
        products.value = []
        if (import.meta.env.DEV) {
          devLoadNotice.value = devCatalogErrorMessage(e)
        }
      } finally {
        loading.value = false
        ready.value = true
        inflight = null
      }
    })()

    return inflight
  }

  const catalog = computed(() => {
    if (source.value === 'api' && products.value.length > 0) {
      const map = new Map<string, SiteProduct>()
      for (const p of products.value) map.set(p.id, p)
      return [...map.values()]
    }
    return staticCatalog()
  })

  const favourites = computed(() => {
    if (source.value === 'api' && products.value.length > 0) {
      return products.value
        .filter((p) => p.homeBestseller === true)
        .slice(0, HOME_BESTSELLER_LIMIT)
    }
    return favouritesProducts.slice(0, HOME_BESTSELLER_LIMIT)
  })

  const magneticBookmarks = computed(() => {
    if (source.value === 'api' && products.value.length > 0) {
      return products.value.filter((p) => matchesBookmarkType(p, 'magnetic'))
    }
    return [...magneticBookmarkProducts]
  })

  return {
    source,
    loading,
    ready,
    products,
    devLoadNotice,
    catalog,
    favourites,
    magneticBookmarks,
    ensureLoaded,
    dismissDevNotice() {
      devLoadNotice.value = null
    },
  }
})
