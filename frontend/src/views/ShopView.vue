<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { apiFetch, ApiError } from '@/lib/api'
import type { Product } from '@/types/product'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()

/** Demo products until your PHP API is wired (replace with API-only list). */
const demoProducts: Product[] = [
  {
    id: 'demo-1',
    slug: 'magnetic-bookmark-forest',
    name: 'Magnetic bookmark — Forest',
    description: 'Pair of magnetic clips, forest green foil.',
    price: 6.5,
    currency: 'USD',
    category: 'bookmarks',
  },
  {
    id: 'demo-2',
    slug: 'desk-calendar-2026',
    name: 'Desk calendar 2026',
    description: 'Stand-up month view, Sunday start.',
    price: 14,
    currency: 'USD',
    category: 'calendars',
  },
  {
    id: 'demo-3',
    slug: 'sticker-sheet-botanical',
    name: 'Sticker sheet — Botanical',
    description: 'Matte vinyl, 24 stickers per sheet.',
    price: 5,
    currency: 'USD',
    category: 'stickers',
  },
]

const products = ref<Product[]>([])
const loadError = ref<string | null>(null)
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  loadError.value = null
  try {
    const fromApi = await apiFetch<Product[]>('/api/products')
    products.value = Array.isArray(fromApi) ? fromApi : []
  } catch (e) {
    if (e instanceof ApiError && e.status === 404) {
      loadError.value = null
      products.value = []
    } else {
      loadError.value = e instanceof Error ? e.message : 'Could not load products'
      products.value = []
    }
  } finally {
    loading.value = false
  }

  if (products.value.length === 0) {
    products.value = demoProducts
  }
})

function formatPrice(p: Product) {
  const cur = p.currency ?? 'USD'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: cur }).format(
    p.price
  )
}
</script>

<template>
  <div class="shop">
    <header class="shop__head">
      <h1 class="shop__title">Shop</h1>
      <p class="shop__sub">
        Products below use demo data until <code class="shop__code">GET /api/products</code> returns
        JSON from your PHP app.
      </p>
    </header>

    <p v-if="loading" class="shop__status" role="status">Loading products…</p>
    <p v-else-if="loadError" class="shop__status shop__status--warn" role="alert">
      {{ loadError }} — showing demo items.
    </p>

    <ul class="grid" role="list">
      <li v-for="p in products" :key="String(p.id)" class="card">
        <div class="card__body">
          <h2 class="card__title">{{ p.name }}</h2>
          <p v-if="p.description" class="card__desc">{{ p.description }}</p>
          <p class="card__price">{{ formatPrice(p) }}</p>
        </div>
        <button type="button" class="card__btn" @click="cart.addProduct(p)">Add to cart</button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.shop__head {
  margin-bottom: 1.5rem;
}

.shop__title {
  margin: 0 0 0.5rem;
  font-size: 1.75rem;
  font-weight: 650;
  letter-spacing: -0.02em;
  color: var(--color-text);
}

.shop__sub {
  margin: 0;
  max-width: 40rem;
  color: var(--color-text-muted);
  line-height: 1.5;
  font-size: 0.95rem;
}

.shop__code {
  font-family: var(--font-mono);
  font-size: 0.85em;
  padding: 0.1rem 0.35rem;
  border-radius: 0.35rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
}

.shop__status {
  margin: 0 0 1rem;
  font-size: 0.95rem;
  color: var(--color-text-muted);
}

.shop__status--warn {
  color: var(--color-warn);
}

.grid {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}

.card {
  display: flex;
  flex-direction: column;
  border-radius: 0.85rem;
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  overflow: hidden;
}

.card__body {
  padding: 1.1rem 1.15rem 0.75rem;
  flex: 1;
}

.card__title {
  margin: 0 0 0.4rem;
  font-size: 1.05rem;
  font-weight: 650;
  color: var(--color-text);
}

.card__desc {
  margin: 0 0 0.75rem;
  font-size: 0.9rem;
  line-height: 1.45;
  color: var(--color-text-muted);
}

.card__price {
  margin: 0;
  font-weight: 600;
  color: var(--color-text);
}

.card__btn {
  margin-top: auto;
  border: none;
  border-top: 1px solid var(--color-border);
  padding: 0.75rem 1rem;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  color: var(--color-accent);
}

.card__btn:hover {
  background: color-mix(in srgb, var(--color-accent) 12%, transparent);
}
</style>
